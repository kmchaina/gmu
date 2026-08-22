<?php

namespace App\Services;

use App\Models\Award;
use App\Models\CallInterest;
use App\Models\ComplianceCalendar;
use App\Models\EthicsRenewal;
use App\Models\FundingCall;
use App\Models\GmuNotification;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\User;
use App\Support\Days;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * In-app notifications (audit finding M4).
 *
 * The notifications table, the bell in the topbar, the unread badge and the
 * notifications page all existed — and nothing in the system ever created a
 * record, so the "internal workflow notifications" promised in the GMU concept
 * were silently never delivered. Every event that a person needs to act on
 * goes through here.
 */
class Notifier
{
    /**
     * @param  User|Collection<int, User>|iterable<User>  $recipients
     */
    public function send(mixed $recipients, string $type, string $title, string $message, ?Model $related = null): int
    {
        $recipients = $recipients instanceof User ? [$recipients] : $recipients;
        $sent = 0;

        foreach ($recipients as $user) {
            if (! $user instanceof User || ! $user->is_active) {
                continue;
            }

            GmuNotification::create([
                'user_id'      => $user->id,
                'type'         => $type,
                'title'        => $title,
                'message'      => $message,
                'related_type' => $related ? $related::class : null,
                'related_id'   => $related?->getKey(),
            ]);

            $sent++;
        }

        return $sent;
    }

    // ── Pre-award ───────────────────────────────────────────────────────────

    /** A new call is open: tell the researchers who could apply. */
    public function callPosted(FundingCall $call): int
    {
        return $this->send(
            $this->researchers(),
            'new_call',
            'New funding call: ' . $call->title,
            sprintf(
                '%s has opened a call closing %s (%s). Express your interest through the portal if you intend to apply.',
                $call->donor?->name ?? 'A donor',
                $call->deadline?->format('j M Y'),
                Days::describe($call->deadline)
            ),
            $call
        );
    }

    /** The GMU has decided who takes a call forward. */
    public function interestDecision(CallInterest $interest): int
    {
        $selected = $interest->status === 'selected';

        return $this->send(
            $interest->user,
            'selection_result',
            $selected ? 'You have been selected to lead a proposal' : 'Call selection outcome',
            $selected
                ? sprintf('You have been selected for "%s". The GMU will be in touch about proposal development.', $interest->fundingCall?->title)
                : sprintf('Another team was selected for "%s". Thank you for expressing interest.', $interest->fundingCall?->title),
            $interest->fundingCall
        );
    }

    /** A review track is waiting on someone. */
    public function reviewRequested(Proposal $proposal, User $reviewer, string $track): int
    {
        return $this->send(
            $reviewer,
            'review_assigned',
            ucfirst($track) . ' review requested',
            sprintf('Proposal "%s" is in internal review and needs your %s assessment.', $proposal->title, $track),
            $proposal
        );
    }

    /** Where a proposal has got to, for the people on it. */
    public function proposalStatusChanged(Proposal $proposal, string $from, string $to): int
    {
        $readable = str_replace('_', ' ', $to);

        return $this->send(
            array_filter([$proposal->leadPi, $proposal->assignedOfficer]),
            'proposal_status',
            'Proposal moved to ' . $readable,
            sprintf('"%s" moved from %s to %s.', $proposal->title, str_replace('_', ' ', $from), $readable),
            $proposal
        );
    }

    // ── Award and post-award ────────────────────────────────────────────────

    public function awardCreated(Award $award): int
    {
        $recipients = array_filter([
            $award->proposal?->leadPi,
            $award->proposal?->assignedOfficer,
        ]);

        return $this->send(
            $recipients,
            'award_created',
            'Award recorded: ' . $award->contract_number,
            sprintf(
                '"%s" runs from %s to %s. Reporting deadlines have been added to the compliance calendar.',
                $award->title,
                $award->start_date?->format('j M Y'),
                $award->end_date?->format('j M Y')
            ),
            $award
        );
    }

    public function reportDue(Report $report, int $daysAhead): int
    {
        $lead = $report->award?->proposal?->leadPi;

        if (! $lead) {
            return 0;
        }

        return $this->send(
            $lead,
            'report_due',
            ucfirst($report->report_type) . ' report due in ' . $daysAhead . ' days',
            sprintf('"%s" for %s is due on %s.', $report->title, $report->award?->title, $report->due_date?->format('j M Y')),
            $report
        );
    }

    /** The GMU has ruled on a submitted report. */
    public function reportDecision(Report $report): int
    {
        $recipients = array_filter([$report->submittedBy, $report->award?->proposal?->leadPi]);

        return $this->send(
            $recipients,
            'report_decision',
            'Report ' . $report->status . ': ' . $report->title,
            $report->status === 'approved'
                ? sprintf('"%s" has been verified and is cleared for submission to the donor.', $report->title)
                : sprintf('"%s" was returned. %s', $report->title, $report->gmu_comments ?: 'See the GMU comments on the report.'),
            $report
        );
    }

    public function complianceDue(ComplianceCalendar $item, int $daysAhead): int
    {
        $lead = $item->award?->proposal?->leadPi;

        if (! $lead) {
            return 0;
        }

        return $this->send(
            $lead,
            'compliance_due',
            'Deliverable due in ' . $daysAhead . ' days',
            sprintf('"%s" (%s) for %s is due on %s.', $item->title, $item->deliverable_type, $item->award?->title, $item->due_date?->format('j M Y')),
            $item
        );
    }

    public function ethicsExpiring(EthicsRenewal $renewal, int $daysAhead): int
    {
        $lead = $renewal->award?->proposal?->leadPi;

        if (! $lead) {
            return 0;
        }

        return $this->send(
            $lead,
            'ethics_renewal',
            'Ethics approval expires in ' . $daysAhead . ' days',
            sprintf('Protocol %s for %s expires on %s. Start the renewal now to avoid a lapse.', $renewal->ethics_number, $renewal->award?->title, $renewal->expiry_date?->format('j M Y')),
            $renewal
        );
    }

    /** Everyone in the GMU office, for things the unit must act on. */
    public function gmuOffice(string $type, string $title, string $message, ?Model $related = null): int
    {
        return $this->send(
            User::whereHas('role', fn($q) => $q->whereIn('name', ['coordinator', 'planning_officer']))
                ->where('is_active', true)->get(),
            $type,
            $title,
            $message,
            $related
        );
    }

    private function researchers(): Collection
    {
        return User::whereHas('role', fn($q) => $q->whereIn('name', User::RESEARCH_ROLES))
            ->where('is_active', true)
            ->get();
    }
}
