<?php

namespace App\Console\Commands;

use App\Mail\DeadlineReminderMail;
use App\Models\ComplianceCalendar;
use App\Models\EthicsRenewal;
use App\Models\Report;
use App\Models\User;
use App\Services\Notifier;
use App\Services\Sms\SmsGateway;
use App\Support\Days;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Deadline reminders for reports, compliance deliverables and ethics renewals.
 *
 * Audit finding M8: this used to match an exact single day — "due 30 days from
 * today" — so if the scheduler did not run that morning (server off, cron
 * missed) the window was skipped permanently. Windows are now "due within N
 * days and not yet reminded", which self-heals after an outage.
 *
 * Mail is also no longer able to take the whole run down: a failure for one
 * recipient is logged and the rest continue. Every reminder additionally
 * creates an in-app notification, which does not depend on SMTP at all
 * (finding M4).
 */
class SendDeadlineReminders extends Command
{
    protected $signature   = 'gmu:send-deadline-reminders {--dry-run : List what would be sent without sending}';
    protected $description = 'Send deadline reminders for reports, compliance events, and ethics renewals.';

    /** Reports and compliance deliverables: 30/14/7-day windows. */
    private const REPORT_WINDOWS = [30, 14, 7];

    /** Ethics renewals need a longer lead time for the regulator. */
    private const ETHICS_WINDOWS = [60, 30];

    private ?SmsGateway $sms = null;

    private int $emails = 0;
    private int $notifications = 0;
    private int $texts = 0;
    private int $failures = 0;

    public function handle(Notifier $notifier, SmsGateway $sms): int
    {
        $dryRun    = (bool) $this->option('dry-run');
        $this->sms = $sms;

        $this->remindReports($notifier, $dryRun);
        $this->remindCompliance($notifier, $dryRun);
        $this->remindEthics($notifier, $dryRun);

        $this->info(sprintf(
            '%s%d email(s), %d in-app notification(s), %d text message(s), %d failure(s).',
            $dryRun ? '[dry run] ' : '',
            $this->emails,
            $this->notifications,
            $this->texts,
            $this->failures
        ));

        if (! $sms->isConfigured() && ! $dryRun) {
            $this->comment('No SMS gateway configured — urgent reminders went by email and in-app only.');
        }

        return self::SUCCESS;
    }

    private function remindReports(Notifier $notifier, bool $dryRun): void
    {
        foreach (self::REPORT_WINDOWS as $days) {
            $flag = "reminder_sent_{$days}";

            $reports = Report::with('award.proposal.leadPi')
                ->where('status', 'pending')
                ->whereDate('due_date', '>=', now()->toDateString())
                ->whereDate('due_date', '<=', now()->addDays($days)->toDateString())
                ->where($flag, false)
                ->get();

            foreach ($reports->groupBy(fn($r) => $r->award?->proposal?->leadPi?->id) as $piId => $group) {
                if (! $piId) {
                    continue;
                }

                $pi = $group->first()->award->proposal->leadPi;

                $reminders = $group->map(fn($r) => [
                    'type'        => 'report',
                    'label'       => ucfirst($r->report_type) . ' Report – ' . $r->title,
                    'due'         => $r->due_date,
                    'days'        => Days::until($r->due_date),
                    'award_title' => $r->award->title,
                ])->values()->all();

                if ($dryRun) {
                    $this->line("  would remind {$pi->name} about {$group->count()} report(s) [{$days}-day window]");
                    continue;
                }

                $this->mail($pi, $reminders, $days);

                // Slide 10 of the GMU concept asks for alerts that reach a PI in
                // the field. Seven days out, email on its own is not enough.
                if ($days <= 7) {
                    $this->text($pi, sprintf(
                        'NIMR GMU: %d report(s) due within %d days, including "%s". Please submit through the portal.',
                        $group->count(),
                        $days,
                        $group->first()->title
                    ));
                }

                foreach ($group as $report) {
                    $this->notifications += $notifier->reportDue($report, Days::until($report->due_date));
                    $report->update([$flag => true]);
                }
            }

            if ($reports->isNotEmpty() && ! $dryRun) {
                $this->notifyOffice($days, $reports->map(fn($r) => [
                    'type'        => 'report',
                    'label'       => ucfirst($r->report_type) . ' Report – ' . $r->title,
                    'due'         => $r->due_date,
                    'days'        => Days::until($r->due_date),
                    'award_title' => $r->award->title,
                ])->values()->all());
            }
        }
    }

    private function remindCompliance(Notifier $notifier, bool $dryRun): void
    {
        foreach (self::REPORT_WINDOWS as $days) {
            $flag = "reminder_sent_{$days}";

            $items = ComplianceCalendar::with('award.proposal.leadPi')
                ->whereIn('status', ['upcoming', 'overdue'])
                ->whereDate('due_date', '>=', now()->toDateString())
                ->whereDate('due_date', '<=', now()->addDays($days)->toDateString())
                ->where($flag, false)
                ->get();

            foreach ($items->groupBy(fn($i) => $i->award?->proposal?->leadPi?->id) as $piId => $group) {
                if (! $piId) {
                    continue;
                }

                $pi = $group->first()->award->proposal->leadPi;

                $reminders = $group->map(fn($i) => [
                    'type'        => 'compliance',
                    'label'       => $i->title . ' (' . $i->deliverable_type . ')',
                    'due'         => $i->due_date,
                    'days'        => Days::until($i->due_date),
                    'award_title' => $i->award->title,
                ])->values()->all();

                if ($dryRun) {
                    $this->line("  would remind {$pi->name} about {$group->count()} deliverable(s) [{$days}-day window]");
                    continue;
                }

                $this->mail($pi, $reminders, $days);

                foreach ($group as $item) {
                    $this->notifications += $notifier->complianceDue($item, Days::until($item->due_date));
                    $item->update([$flag => true]);
                }
            }
        }
    }

    private function remindEthics(Notifier $notifier, bool $dryRun): void
    {
        foreach (self::ETHICS_WINDOWS as $days) {
            $flag = "reminder_sent_{$days}";

            $items = EthicsRenewal::with('award.proposal.leadPi')
                ->whereDate('expiry_date', '>=', now()->toDateString())
                ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
                ->whereNotIn('renewal_status', ['renewed', 'expired'])
                ->where($flag, false)
                ->get();

            foreach ($items->groupBy(fn($i) => $i->award?->proposal?->leadPi?->id) as $piId => $group) {
                if (! $piId) {
                    continue;
                }

                $pi = $group->first()->award->proposal->leadPi;

                $reminders = $group->map(fn($i) => [
                    'type'        => 'ethics',
                    'label'       => 'Protocol ' . $i->ethics_number,
                    'due'         => $i->expiry_date,
                    'days'        => Days::until($i->expiry_date),
                    'award_title' => $i->award->title,
                ])->values()->all();

                if ($dryRun) {
                    $this->line("  would remind {$pi->name} about {$group->count()} protocol(s) [{$days}-day window]");
                    continue;
                }

                $this->mail($pi, $reminders, $days);

                foreach ($group as $item) {
                    $this->notifications += $notifier->ethicsExpiring($item, Days::until($item->expiry_date));
                    $item->update([$flag => true]);
                }
            }
        }
    }

    /**
     * One recipient's failure must not stop the rest of the run.
     */
    private function mail(User $recipient, array $reminders, int $days): void
    {
        try {
            Mail::to($recipient->email)->send(new DeadlineReminderMail($recipient->name, $reminders, $days));
            $this->emails++;
        } catch (\Throwable $e) {
            $this->failures++;
            $this->warn("  mail to {$recipient->email} failed: {$e->getMessage()}");
            Log::error('Deadline reminder mail failed', [
                'recipient' => $recipient->email,
                'window'    => $days,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * A short message to a PI's phone. Unavailable until a gateway is
     * configured — the null driver logs rather than pretending to deliver.
     */
    private function text(User $recipient, string $message): void
    {
        if (blank($recipient->phone) || ! $this->sms?->isConfigured()) {
            return;
        }

        if ($this->sms->send($recipient->phone, $message)) {
            $this->texts++;
        } else {
            $this->failures++;
        }
    }

    private function notifyOffice(int $days, array $reminders): void
    {
        $staff = User::whereHas('role', fn($q) => $q->whereIn('name', ['coordinator', 'planning_officer']))
            ->where('is_active', true)
            ->get();

        foreach ($staff as $user) {
            $this->mail($user, $reminders, $days);
        }
    }
}
