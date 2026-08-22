<?php

namespace App\Console\Commands;

use App\Mail\WeeklyDigestMail;
use App\Models\Award;
use App\Models\ComplianceCalendar;
use App\Models\EthicsRenewal;
use App\Models\FundingCall;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\User;
use App\Support\Days;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigest extends Command
{
    protected $signature   = 'gmu:send-weekly-digest';
    protected $description = 'Send a weekly summary digest email to GMU coordinators and planning officers.';

    public function handle(): int
    {
        $stats    = $this->buildStats();
        $upcoming = $this->buildUpcoming();
        $overdue  = $this->buildOverdue();
        $thisWeek = $this->buildThisWeek();

        $recipients = User::whereHas('role', fn($q) => $q->whereIn('name', ['coordinator', 'planning_officer']))
            ->where('is_active', true)
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn('No active coordinators or planning officers found.');
            return Command::SUCCESS;
        }

        foreach ($recipients as $user) {
            try {
                Mail::to($user->email)->send(
                    new WeeklyDigestMail($user->name, $stats, $upcoming, $overdue, $thisWeek)
                );
            } catch (\Throwable $e) {
                $this->warn("  digest to {$user->email} failed: {$e->getMessage()}");
                \Illuminate\Support\Facades\Log::error('Weekly digest failed', [
                    'recipient' => $user->email,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $this->info("Weekly digest sent to {$recipients->count()} recipient(s).");
        return Command::SUCCESS;
    }

    private function buildStats(): array
    {
        return [
            'active_awards'            => Award::where('status', 'active')->count(),
            'open_calls'               => FundingCall::where('status', 'open')->count(),
            'proposals_pending_review' => Proposal::whereIn('status', ['submitted', 'internal_review'])->count(),
            'overdue_reports'          => Report::where('status', 'pending')
                                            ->where('due_date', '<', now())
                                            ->count(),
        ];
    }

    /**
     * What moved in the last seven days.
     *
     * Slide 15 of the GMU concept asks the digest to summarise new calls
     * received, calls closing soon, and new awards and submissions. The digest
     * previously carried deadlines and overdue items only.
     */
    private function buildThisWeek(): array
    {
        $since = now()->subWeek();

        return [
            'new_calls' => FundingCall::with('donor')
                ->where('created_at', '>=', $since)
                ->orderBy('deadline')
                ->get()
                ->map(fn($call) => [
                    'title' => $call->title,
                    'donor' => $call->donor?->acronym ?? $call->donor?->name,
                    'due'   => $call->deadline,
                    'days'  => Days::until($call->deadline),
                ])->all(),

            'closing_soon' => FundingCall::with('donor')
                ->where('status', 'open')
                ->whereBetween('deadline', [now(), now()->addDays(30)])
                ->orderBy('deadline')
                ->get()
                ->map(fn($call) => [
                    'title' => $call->title,
                    'donor' => $call->donor?->acronym ?? $call->donor?->name,
                    'due'   => $call->deadline,
                    'days'  => Days::until($call->deadline),
                ])->all(),

            'new_awards' => Award::with('donor')
                ->where('created_at', '>=', $since)
                ->get()
                ->map(fn($award) => [
                    'title'  => $award->title,
                    'donor'  => $award->donor?->acronym ?? $award->donor?->name,
                    'amount' => $award->currency . ' ' . number_format((float) $award->total_budget, 0),
                ])->all(),

            'submissions' => Proposal::with('fundingCall.donor', 'leadPi')
                ->whereNotNull('submitted_at')
                ->where('submitted_at', '>=', $since)
                ->get()
                ->map(fn($proposal) => [
                    'title' => $proposal->title,
                    'pi'    => $proposal->leadPi?->name,
                    'donor' => $proposal->fundingCall?->donor?->acronym,
                ])->all(),

            'awaiting_closeout' => Award::whereDate('end_date', '<', now())
                ->where('status', '!=', 'completed')
                ->get()
                ->map(fn($award) => [
                    'title' => $award->title,
                    'ended' => $award->end_date,
                    'days'  => Days::overdueBy($award->end_date),
                ])->all(),
        ];
    }

    private function buildUpcoming(): array
    {
        $window  = now()->addDays(14);
        $items   = [];

        // Reports due in next 14 days
        $reports = Report::with('award')
            ->where('status', 'pending')
            ->whereBetween('due_date', [now(), $window])
            ->orderBy('due_date')
            ->get();

        foreach ($reports as $r) {
            $items[] = [
                'type'        => 'report',
                'label'       => $r->report_type . ' Report',
                'award_title' => $r->award->title,
                'due'         => $r->due_date,
                'days'        => Days::until($r->due_date),
            ];
        }

        // Compliance events due in next 14 days
        $compliance = ComplianceCalendar::with('award')
            ->where('status', 'upcoming')
            ->whereBetween('due_date', [now(), $window])
            ->orderBy('due_date')
            ->get();

        foreach ($compliance as $item) {
            $items[] = [
                'type'        => 'compliance',
                'label'       => $item->title . ' (' . $item->deliverable_type . ')',
                'award_title' => $item->award->title,
                'due'         => $item->due_date,
                'days'        => Days::until($item->due_date),
            ];
        }

        // Ethics renewals expiring in next 14 days
        $ethics = EthicsRenewal::with('award')
            ->whereBetween('expiry_date', [now(), $window])
            ->whereNotIn('renewal_status', ['renewed', 'expired'])
            ->orderBy('expiry_date')
            ->get();

        foreach ($ethics as $item) {
            $items[] = [
                'type'        => 'ethics',
                'label'       => 'Protocol ' . $item->ethics_number,
                'award_title' => $item->award->title,
                'due'         => $item->expiry_date,
                'days'        => Days::until($item->expiry_date),
            ];
        }

        usort($items, fn($a, $b) => $a['days'] <=> $b['days']);
        return $items;
    }

    private function buildOverdue(): array
    {
        $items = [];

        // Overdue reports
        $reports = Report::with('award')
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->get();

        foreach ($reports as $r) {
            $items[] = [
                'type'        => 'report',
                'label'       => $r->report_type . ' Report',
                'award_title' => $r->award->title,
                'overdue_days' => Days::overdueBy($r->due_date),
            ];
        }

        // Overdue compliance events
        $compliance = ComplianceCalendar::with('award')
            ->where('status', 'upcoming')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->get();

        foreach ($compliance as $item) {
            $items[] = [
                'type'        => 'compliance',
                'label'       => $item->title,
                'award_title' => $item->award->title,
                'overdue_days' => Days::overdueBy($item->due_date),
            ];
        }

        // Expired ethics not yet renewed
        $ethics = EthicsRenewal::with('award')
            ->where('expiry_date', '<', now())
            ->whereNotIn('renewal_status', ['renewed'])
            ->orderBy('expiry_date')
            ->get();

        foreach ($ethics as $item) {
            $items[] = [
                'type'        => 'ethics',
                'label'       => 'Protocol ' . $item->ethics_number,
                'award_title' => $item->award->title,
                'overdue_days' => Days::overdueBy($item->expiry_date),
            ];
        }

        usort($items, fn($a, $b) => $b['overdue_days'] <=> $a['overdue_days']);
        return $items;
    }
}
