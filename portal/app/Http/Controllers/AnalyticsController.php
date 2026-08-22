<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\FundingCall;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\Donor;
use App\Models\SubAward;
use App\Services\AuditReadinessScore;
use App\Models\EthicsRenewal;
use App\Models\ComplianceCalendar;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        // ── Portfolio KPIs ───────────────────────────────────────────────────
        $kpis = [
            'total_awards'          => Award::count(),
            'active_awards'         => Award::where('status', 'active')->count(),
            'total_funding'         => Award::where('status', 'active')->sum('base_total_budget'),
            'base_currency'         => Money::base(),
            'open_calls'            => FundingCall::where('status', 'open')->count(),
            'proposals_total'       => Proposal::count(),
            'proposals_submitted'   => Proposal::submitted()->count(),
            'proposals_funded'      => Proposal::funded()->count(),
            'proposals_awaiting'    => Proposal::whereNotIn('status', array_merge(Proposal::DECIDED_STATUSES, ['withdrawn']))->count(),
            'success_rate'          => $this->successRate(),
            'success_rate_basis'    => Proposal::decided()->count(),
            'overdue_reports'       => Report::where('status', 'pending')->where('due_date', '<', now())->count(),
            'compliance_overdue'    => ComplianceCalendar::where('status', 'upcoming')->where('due_date', '<', now())->count(),
            'ethics_expiring_soon'  => EthicsRenewal::where('expiry_date', '<=', now()->addDays(60))
                                        ->whereNotIn('renewal_status', ['renewed', 'expired'])->count(),
            // Slide 12 of the concept: an annual audit-readiness score.
            'audit_readiness'       => app(AuditReadinessScore::class)->portfolioAverage(),
            'sub_award_exposure'    => SubAward::whereIn('status', ['active', 'completed'])->sum('base_amount'),
            'open_sub_awards'       => SubAward::whereIn('status', ['draft', 'active', 'suspended'])->count(),
            'awaiting_closeout'     => Award::whereDate('end_date', '<', now())
                                        ->where('status', '!=', 'completed')->count(),
        ];

        // ── Awards by Status (pie) ───────────────────────────────────────────
        $awardsByStatus = Award::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // ── Awards per Year (bar) ────────────────────────────────────────────
        // Finding M12: YEAR() is MySQL-only, and the grant year is the year the
        // award started, not the day somebody typed the record in.
        $awardsPerYear = Award::whereNotNull('start_date')
            ->get(['start_date'])
            ->groupBy(fn($award) => $award->start_date->format('Y'))
            ->map->count()
            ->sortKeys();

        // ── Funding by Donor (horizontal bar, top 8) ─────────────────────────
        // Finding M3: summed in the reporting currency, not raw contract amounts.
        $fundingByDonor = Award::select('donor_id', DB::raw('SUM(base_total_budget) as total'))
            ->with('donor:id,name,acronym')
            ->groupBy('donor_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn($a) => [
                'label'  => $a->donor?->acronym ?? $a->donor?->name ?? 'Unknown',
                'amount' => (float) $a->total,
            ]);

        // ── Proposals by Status (donut) ──────────────────────────────────────
        $proposalsByStatus = Proposal::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // ── Report submission timeliness (last 12 months) ────────────────────
        $reportTimeliness = $this->reportTimeliness();

        // ── Budget utilisation across active awards ───────────────────────────
        $budgetUtilisation = $this->budgetUtilisation();

        // ── Recent audit log entries ─────────────────────────────────────────
        $recentActivity = \App\Models\ActivityLog::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('analytics.index', compact(
            'kpis',
            'awardsByStatus',
            'awardsPerYear',
            'fundingByDonor',
            'proposalsByStatus',
            'reportTimeliness',
            'budgetUtilisation',
            'recentActivity',
        ));
    }

    /**
     * Share of proposals the donor ruled on that were funded (finding M1).
     *
     * Withdrawn proposals never reached a donor, and internally "approved" ones
     * are still awaiting a decision — neither belongs in the ratio.
     */
    private function successRate(): float
    {
        $decided = Proposal::decided()->count();
        $funded  = Proposal::funded()->count();

        return $decided > 0 ? round(($funded / $decided) * 100, 1) : 0;
    }

    private function reportTimeliness(): array
    {
        // Submitted reports in the last 12 months — on-time vs late
        $reports = Report::whereIn('status', ['submitted', 'approved'])
            ->whereNotNull('submitted_date')
            ->where('submitted_date', '>=', now()->subMonths(12))
            ->get(['due_date', 'submitted_date']);

        $onTime = $reports->filter(fn($r) => $r->submitted_date <= $r->due_date)->count();
        $late   = $reports->count() - $onTime;

        return ['on_time' => $onTime, 'late' => $late, 'total' => $reports->count()];
    }

    private function budgetUtilisation(): array
    {
        return Award::where('status', 'active')
            ->withSum('budgets as allocated_total', 'base_allocated_amount')
            ->withSum('budgets as spent_total', 'base_spent_amount')
            ->get()
            ->map(function ($award) {
                $allocated = (float) $award->allocated_total;
                $spent     = (float) $award->spent_total;
                $pct       = $allocated > 0 ? round(($spent / $allocated) * 100, 1) : 0;
                return [
                    'label'     => $award->contract_number ?? $award->title,
                    'allocated' => (float) $allocated,
                    'spent'     => (float) $spent,
                    'pct'       => $pct,
                    'light'     => $pct > 100 ? 'red' : ($pct > 85 ? 'yellow' : 'green'),
                ];
            })
            ->sortByDesc('pct')
            ->values()
            ->all();
    }
}
