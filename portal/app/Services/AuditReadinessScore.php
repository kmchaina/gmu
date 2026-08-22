<?php

namespace App\Services;

use App\Models\Award;
use App\Support\Days;

/**
 * The annual audit-readiness score promised on slide 12 of the GMU concept.
 *
 * An auditor arriving at NIMR asks the same questions every time: are the
 * reports in and on time, is the expenditure traceable to documents, is the
 * ethics approval current, was the grant monitored, is the contract on file,
 * and — for finished work — was it closed out properly. This scores an award
 * against exactly those, so the unit knows where it stands before anyone asks.
 */
class AuditReadinessScore
{
    /** Each dimension, with the weight it carries out of 100. */
    public const DIMENSIONS = [
        'reporting'   => ['weight' => 25, 'label' => 'Reporting discipline'],
        'financial'   => ['weight' => 25, 'label' => 'Traceable expenditure'],
        'ethics'      => ['weight' => 15, 'label' => 'Ethics approval current'],
        'monitoring'  => ['weight' => 15, 'label' => 'Monitoring and oversight'],
        'contractual' => ['weight' => 10, 'label' => 'Contract documentation'],
        'closeout'    => ['weight' => 10, 'label' => 'Closeout discipline'],
    ];

    /**
     * @return array{score:int, band:string, dimensions:array<string,array>, gaps:list<string>}
     */
    public function for(Award $award): array
    {
        $award->loadMissing(['reports', 'budgets', 'expenditures', 'ethicsRenewals', 'monitoringVisits', 'subAwards', 'closeout']);

        $dimensions = [
            'reporting'   => $this->reporting($award),
            'financial'   => $this->financial($award),
            'ethics'      => $this->ethics($award),
            'monitoring'  => $this->monitoring($award),
            'contractual' => $this->contractual($award),
            'closeout'    => $this->closeout($award),
        ];

        $score = 0;
        $gaps  = [];

        foreach ($dimensions as $key => $dimension) {
            $score += $dimension['earned'];

            foreach ($dimension['gaps'] as $gap) {
                $gaps[] = $gap;
            }
        }

        $score = (int) round($score);

        return [
            'score'      => $score,
            'band'       => $this->band($score),
            'dimensions' => $dimensions,
            'gaps'       => $gaps,
        ];
    }

    /** Portfolio average, for the analytics dashboard. */
    public function portfolioAverage(?iterable $awards = null): int
    {
        $awards ??= Award::with(['reports', 'budgets', 'expenditures', 'ethicsRenewals', 'monitoringVisits', 'subAwards', 'closeout'])
            ->whereIn('status', ['active', 'completed'])
            ->get();

        $scores = collect($awards)->map(fn(Award $award) => $this->for($award)['score']);

        return $scores->isEmpty() ? 0 : (int) round($scores->avg());
    }

    public function band(int $score): string
    {
        return match (true) {
            $score >= 85 => 'ready',
            $score >= 65 => 'minor gaps',
            $score >= 45 => 'significant gaps',
            default      => 'not ready',
        };
    }

    // ── dimensions ──────────────────────────────────────────────────────────

    private function reporting(Award $award): array
    {
        $due = $award->reports->filter(fn($r) => Days::isPast($r->due_date));

        if ($due->isEmpty()) {
            return $this->dimension('reporting', 1.0, []);
        }

        $filed  = $due->whereIn('status', ['submitted', 'under_review', 'approved']);
        $onTime = $filed->filter(fn($r) => $r->submitted_date && $r->submitted_date->lte($r->due_date));

        $ratio = ($filed->count() / $due->count()) * 0.7 + ($onTime->count() / $due->count()) * 0.3;
        $gaps  = [];

        $outstanding = $due->count() - $filed->count();

        if ($outstanding > 0) {
            $gaps[] = "{$outstanding} report(s) past their due date and not filed";
        }

        if ($filed->count() > $onTime->count()) {
            $gaps[] = ($filed->count() - $onTime->count()) . ' report(s) filed late';
        }

        return $this->dimension('reporting', $ratio, $gaps);
    }

    private function financial(Award $award): array
    {
        $spent = (float) $award->budgets->sum('spent_amount');
        $gaps  = [];

        if ($award->budgets->isEmpty()) {
            return $this->dimension('financial', 0.0, ['No budget lines recorded for this award']);
        }

        if ($spent <= 0) {
            // Nothing spent yet: nothing to trace, so this is not a failure.
            return $this->dimension('financial', 1.0, []);
        }

        // How much of the reported spend is backed by ledger entries?
        $ledger  = (float) $award->expenditures->sum('amount');
        $covered = min(1.0, $ledger / $spent);

        if ($covered < 0.99) {
            $missing = round(($spent - $ledger), 2);
            $gaps[] = 'Expenditure of ' . number_format($missing, 2) . ' ' . $award->currency . ' has no ledger entries behind it';
        }

        $unreferenced = $award->expenditures->filter(fn($e) => blank($e->reference))->count();

        if ($unreferenced > 0) {
            $gaps[] = "{$unreferenced} ledger entr(y/ies) with no voucher or invoice reference";
            $covered -= min(0.2, $unreferenced / max(1, $award->expenditures->count()) * 0.2);
        }

        return $this->dimension('financial', max(0, $covered), $gaps);
    }

    private function ethics(Award $award): array
    {
        if ($award->ethicsRenewals->isEmpty()) {
            return $this->dimension('ethics', 0.0, ['No ethics approval recorded against the award']);
        }

        $expired = $award->ethicsRenewals->filter(fn($e) => Days::isPast($e->expiry_date) && $e->renewal_status !== 'renewed');

        if ($expired->isNotEmpty()) {
            return $this->dimension('ethics', 0.0, [$expired->count() . ' ethics approval(s) expired without renewal']);
        }

        $lapsingSoon = $award->ethicsRenewals->filter(fn($e) => Days::until($e->expiry_date) <= 30);

        return $lapsingSoon->isNotEmpty()
            ? $this->dimension('ethics', 0.6, ['Ethics approval expires within 30 days and has not been renewed'])
            : $this->dimension('ethics', 1.0, []);
    }

    private function monitoring(Award $award): array
    {
        $monthsRunning = max(1, (int) $award->start_date?->diffInMonths(now()));
        $expected      = max(1, intdiv($monthsRunning, 12) + 1); // roughly one visit a year
        $actual        = $award->monitoringVisits->count();
        $gaps          = [];

        if ($actual < $expected) {
            $gaps[] = "Only {$actual} monitoring visit(s) recorded where {$expected} would be expected by now";
        }

        $unmonitoredSubs = $award->subAwards->filter(
            fn($s) => $s->isOpen() && ($s->last_monitored_on === null || Days::overdueBy($s->last_monitored_on) > 365)
        );

        if ($unmonitoredSubs->isNotEmpty()) {
            $gaps[] = $unmonitoredSubs->count() . ' open sub-award(s) not monitored in the past year';
        }

        $ratio = min(1.0, $actual / $expected) - ($unmonitoredSubs->isNotEmpty() ? 0.3 : 0);

        return $this->dimension('monitoring', max(0, $ratio), $gaps);
    }

    private function contractual(Award $award): array
    {
        $gaps  = [];
        $ratio = 1.0;

        if (blank($award->signed_contract)) {
            $gaps[] = 'No signed contract on file';
            $ratio -= 0.6;
        }

        $undocumented = $award->subAwards->filter(fn($s) => blank($s->agreement_file) && $s->status !== 'draft');

        if ($undocumented->isNotEmpty()) {
            $gaps[] = $undocumented->count() . ' sub-award(s) with no signed agreement on file';
            $ratio -= 0.3;
        }

        $noDueDiligence = $award->subAwards->filter(fn($s) => $s->status === 'active' && $s->due_diligence_completed_on === null);

        if ($noDueDiligence->isNotEmpty()) {
            $gaps[] = $noDueDiligence->count() . ' active sub-award(s) with no due diligence recorded';
            $ratio -= 0.3;
        }

        return $this->dimension('contractual', max(0, $ratio), $gaps);
    }

    private function closeout(Award $award): array
    {
        $ended = Days::isPast($award->end_date);

        if (! $ended && $award->status === 'active') {
            return $this->dimension('closeout', 1.0, []); // nothing to close yet
        }

        $closeout = $award->closeout;

        if (! $closeout) {
            return $this->dimension('closeout', 0.0, ['The award has ended and closeout has not been started']);
        }

        $gaps = [];

        if ($closeout->status !== 'completed') {
            $outstanding = count($closeout->outstandingSteps());
            $gaps[] = "Closeout is {$closeout->progress()}% complete, {$outstanding} step(s) outstanding";
        }

        if (! $closeout->hasLessons()) {
            $gaps[] = 'No lessons learned recorded';
        }

        return $this->dimension('closeout', $closeout->progress() / 100, $gaps);
    }

    private function dimension(string $key, float $ratio, array $gaps): array
    {
        $weight = self::DIMENSIONS[$key]['weight'];
        $ratio  = max(0.0, min(1.0, $ratio));

        return [
            'label'  => self::DIMENSIONS[$key]['label'],
            'weight' => $weight,
            'ratio'  => round($ratio, 2),
            'earned' => round($ratio * $weight, 1),
            'gaps'   => $gaps,
        ];
    }
}
