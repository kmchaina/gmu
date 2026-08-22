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

        $recipients = User::whereHas('role', fn($q) => $q->whereIn('name', ['coordinator', 'planning_officer']))
            ->where('is_active', true)
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn('No active coordinators or planning officers found.');
            return Command::SUCCESS;
        }

        foreach ($recipients as $user) {
            Mail::to($user->email)->send(
                new WeeklyDigestMail($user->name, $stats, $upcoming, $overdue)
            );
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
                'days'        => (int) now()->diffInDays($r->due_date, false),
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
                'days'        => (int) now()->diffInDays($item->due_date, false),
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
                'days'        => (int) now()->diffInDays($item->expiry_date, false),
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
                'overdue_days' => (int) $r->due_date->diffInDays(now()),
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
                'overdue_days' => (int) $item->due_date->diffInDays(now()),
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
                'overdue_days' => (int) $item->expiry_date->diffInDays(now()),
            ];
        }

        usort($items, fn($a, $b) => $b['overdue_days'] <=> $a['overdue_days']);
        return $items;
    }
}
