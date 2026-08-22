<?php

namespace App\Console\Commands;

use App\Mail\DeadlineReminderMail;
use App\Models\ComplianceCalendar;
use App\Models\EthicsRenewal;
use App\Models\Report;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDeadlineReminders extends Command
{
    protected $signature   = 'gmu:send-deadline-reminders';
    protected $description = 'Send deadline reminder emails for reports, compliance events, and ethics renewals.';

    // Reports & compliance: 30/14/7-day windows
    private const REPORT_WINDOWS     = [30, 14, 7];
    // Ethics renewals: 60/30-day windows (longer lead time for regulatory renewal)
    private const ETHICS_WINDOWS     = [60, 30];

    public function handle(): int
    {
        $totalSent = 0;

        // ── Reports ──────────────────────────────────────────────────────────
        foreach (self::REPORT_WINDOWS as $days) {
            $flag  = "reminder_sent_{$days}";
            [$start, $end] = $this->window($days);

            $reports = Report::with('award.proposal.leadPi')
                ->where('status', 'pending')
                ->whereBetween('due_date', [$start, $end])
                ->where($flag, false)
                ->get();

            // Group by PI so each PI gets one consolidated email per window
            foreach ($reports->groupBy(fn($r) => optional($r->award->proposal->leadPi)->id) as $piId => $group) {
                if (!$piId) continue;
                $pi = $group->first()->award->proposal->leadPi;
                $reminders = $group->map(fn($r) => [
                    'type'        => 'report',
                    'label'       => ucfirst($r->report_type) . ' Report – ' . $r->title,
                    'due'         => $r->due_date,
                    'days'        => $days,
                    'award_title' => $r->award->title,
                ])->values()->all();

                Mail::to($pi->email)->send(new DeadlineReminderMail($pi->name, $reminders, $days));
                $group->each->update([$flag => true]);
                $totalSent++;
            }

            // Also notify GMU staff once per window if there's anything due
            if ($reports->isNotEmpty()) {
                $this->notifyStaff($days, $reports->map(fn($r) => [
                    'type'        => 'report',
                    'label'       => ucfirst($r->report_type) . ' Report – ' . $r->title,
                    'due'         => $r->due_date,
                    'days'        => $days,
                    'award_title' => $r->award->title,
                ])->values()->all());
                $totalSent++;
            }
        }

        // ── Compliance Calendar ──────────────────────────────────────────────
        foreach (self::REPORT_WINDOWS as $days) {
            $flag  = "reminder_sent_{$days}";
            [$start, $end] = $this->window($days);

            $items = ComplianceCalendar::with('award.proposal.leadPi')
                ->where('status', 'upcoming')
                ->whereBetween('due_date', [$start, $end])
                ->where($flag, false)
                ->get();

            foreach ($items->groupBy(fn($i) => optional($i->award->proposal->leadPi)->id) as $piId => $group) {
                if (!$piId) continue;
                $pi = $group->first()->award->proposal->leadPi;
                $reminders = $group->map(fn($i) => [
                    'type'        => 'compliance',
                    'label'       => $i->title . ' (' . $i->deliverable_type . ')',
                    'due'         => $i->due_date,
                    'days'        => $days,
                    'award_title' => $i->award->title,
                ])->values()->all();

                Mail::to($pi->email)->send(new DeadlineReminderMail($pi->name, $reminders, $days));
                $group->each->update([$flag => true]);
                $totalSent++;
            }
        }

        // ── Ethics Renewals ──────────────────────────────────────────────────
        foreach (self::ETHICS_WINDOWS as $days) {
            $flag  = "reminder_sent_{$days}";
            [$start, $end] = $this->window($days);

            $items = EthicsRenewal::with('award.proposal.leadPi')
                ->whereBetween('expiry_date', [$start, $end])
                ->whereNotIn('renewal_status', ['renewed', 'expired'])
                ->where($flag, false)
                ->get();

            foreach ($items->groupBy(fn($i) => optional($i->award->proposal->leadPi)->id) as $piId => $group) {
                if (!$piId) continue;
                $pi = $group->first()->award->proposal->leadPi;
                $reminders = $group->map(fn($i) => [
                    'type'        => 'ethics',
                    'label'       => 'Protocol ' . $i->ethics_number,
                    'due'         => $i->expiry_date,
                    'days'        => $days,
                    'award_title' => $i->award->title,
                ])->values()->all();

                Mail::to($pi->email)->send(new DeadlineReminderMail($pi->name, $reminders, $days));
                $group->each->update([$flag => true]);
                $totalSent++;
            }
        }

        $this->info("Deadline reminders dispatched: {$totalSent} email(s) sent.");
        return Command::SUCCESS;
    }

    private function window(int $days): array
    {
        return [
            now()->addDays($days)->startOfDay(),
            now()->addDays($days)->endOfDay(),
        ];
    }

    private function notifyStaff(int $days, array $reminders): void
    {
        $staff = User::whereHas('role', fn($q) => $q->whereIn('name', ['coordinator', 'planning_officer']))
            ->where('is_active', true)
            ->get();

        foreach ($staff as $user) {
            Mail::to($user->email)->send(new DeadlineReminderMail($user->name, $reminders, $days));
        }
    }
}
