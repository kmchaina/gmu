<?php

namespace App\Services;

use App\Models\Award;
use App\Models\Report;
use Illuminate\Support\Carbon;

/**
 * Builds an award's reporting schedule and compliance calendar at setup
 * (audit finding M6).
 *
 * Step B6 of the GM-BPF says the compliance calendar is generated when an
 * award is set up. Until now every deadline had to be typed in by hand, which
 * is exactly the manual tracking the GMU was created to replace.
 *
 * Two kinds of record come out of this:
 *   • reports — the donor deliverables a PI has to submit, which the reminder
 *     job, the dashboard and the traffic light all key on;
 *   • compliance calendar entries — the other obligations around them: the
 *     kickoff briefing, the mid-term review, closeout.
 */
class ComplianceScheduleGenerator
{
    /** Months between reporting periods. */
    private const FREQUENCIES = [
        'monthly'     => 1,
        'quarterly'   => 3,
        'semi_annual' => 6,
        'annual'      => 12,
    ];

    /** Days after a period ends before the report is due to the donor. */
    private const GRACE_DAYS = 30;

    public function __construct(private readonly ?Notifier $notifier = null) {}

    /**
     * @return array{reports:int, compliance:int}
     */
    public function generate(Award $award, bool $force = false): array
    {
        if ($award->schedule_generated && ! $force) {
            return ['reports' => 0, 'compliance' => 0];
        }

        if (! $award->start_date || ! $award->end_date) {
            return ['reports' => 0, 'compliance' => 0];
        }

        $created = [
            'reports'    => $this->createReportingSchedule($award),
            'compliance' => $this->createMilestones($award),
        ];

        $award->forceFill(['schedule_generated' => true])->save();

        return $created;
    }

    public static function frequencies(): array
    {
        return [
            'monthly'     => 'Monthly',
            'quarterly'   => 'Quarterly',
            'semi_annual' => 'Every six months',
            'annual'      => 'Annually',
        ];
    }

    /**
     * One technical and one financial report per reporting period, each due a
     * month after the period it covers.
     */
    private function createReportingSchedule(Award $award): int
    {
        $months = self::FREQUENCIES[$award->reporting_frequency] ?? 3;
        $end    = Carbon::parse($award->end_date)->startOfDay();

        $periodStart = Carbon::parse($award->start_date)->startOfDay();
        $created     = 0;
        $period      = 1;

        while ($periodStart->lt($end)) {
            $periodEnd = (clone $periodStart)->addMonths($months)->subDay();

            if ($periodEnd->gt($end)) {
                $periodEnd = clone $end;
            }

            $dueDate = (clone $periodEnd)->addDays(self::GRACE_DAYS);
            $isFinal = $periodEnd->equalTo($end);

            foreach (['technical', 'financial'] as $type) {
                $title = $isFinal
                    ? 'Final ' . $type . ' report'
                    : ucfirst($type) . ' report — period ' . $period;

                $exists = Report::where('award_id', $award->id)
                    ->where('report_type', $type)
                    ->whereDate('due_date', $dueDate->toDateString())
                    ->exists();

                if ($exists) {
                    continue;
                }

                Report::create([
                    'award_id'     => $award->id,
                    'report_type'  => $type,
                    'title'        => $title,
                    'period_start' => $periodStart->toDateString(),
                    'period_end'   => $periodEnd->toDateString(),
                    'due_date'     => $dueDate->toDateString(),
                    'status'       => 'pending',
                ]);

                $created++;
            }

            $periodStart = (clone $periodEnd)->addDay();
            $period++;

            if ($period > 60) { // guard against a malformed award period
                break;
            }
        }

        return $created;
    }

    /**
     * The obligations either side of the reporting cycle.
     */
    private function createMilestones(Award $award): int
    {
        $start = Carbon::parse($award->start_date)->startOfDay();
        $end   = Carbon::parse($award->end_date)->startOfDay();

        $milestones = [
            [
                'deliverable_type' => 'other',
                'title'            => 'Kickoff briefing with the PI',
                'due_date'         => (clone $start)->addDays(14),
                'notes'            => 'Compliance briefing, donor terms walkthrough, reporting calendar handover.',
            ],
            [
                'deliverable_type' => 'site_visit',
                'title'            => 'Mid-term monitoring visit',
                'due_date'         => $start->copy()->addSeconds((int) ($start->diffInSeconds($end) / 2)),
                'notes'            => 'Progress against milestones, expenditure verification, records check.',
            ],
            [
                'deliverable_type' => 'audit',
                'title'            => 'Closeout: obligations and final reconciliation',
                'due_date'         => (clone $end)->addDays(60),
                'notes'            => 'Final technical and financial reports submitted, contractual obligations completed, lessons learned recorded.',
            ],
        ];

        $created = 0;

        foreach ($milestones as $milestone) {
            $exists = $award->complianceCalendar()
                ->where('title', $milestone['title'])
                ->exists();

            if ($exists) {
                continue;
            }

            $award->complianceCalendar()->create([
                'deliverable_type' => $milestone['deliverable_type'],
                'title'            => $milestone['title'],
                'due_date'         => $milestone['due_date']->toDateString(),
                'status'           => 'upcoming',
                'notes'            => $milestone['notes'],
            ]);

            $created++;
        }

        return $created;
    }
}
