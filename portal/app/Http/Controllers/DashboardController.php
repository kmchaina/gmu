<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\ComplianceCalendar;
use App\Models\FundingCall;
use App\Models\Proposal;
use App\Models\Report;
use App\Services\AI\ComplianceRiskEngine;
use Illuminate\Http\Request;

/**
 * Two dashboards behind one route.
 *
 * GMU officers see the portfolio. Researchers see their own grants, their own
 * deliverables and their own compliance flags — the "PI dashboards showing
 * deliverables and compliance flags" of slide 9, which previously showed a
 * researcher institution-wide counts and nothing they had to act on.
 */
class DashboardController extends Controller
{
    public function index(ComplianceRiskEngine $riskEngine)
    {
        $user = auth()->user();

        // Both dashboards below are built on the external grants modules. While
        // those are switched off, everyone lands on the seed grant dashboard.
        if (! config('gmu.external_grants')) {
            return app(SeedGrant\DashboardController::class)->render($user);
        }

        return $user->isGmuStaff()
            ? $this->officeDashboard()
            : $this->researcherDashboard($user, $riskEngine);
    }

    private function officeDashboard()
    {
        $stats = [
            'open_calls'         => FundingCall::where('status', 'open')->count(),
            'calls_closing_soon' => FundingCall::where('status', 'open')
                                        ->whereDate('deadline', '<=', now()->addDays(30))->count(),
            'active_proposals'   => Proposal::whereNotIn('status', ['awarded', 'declined', 'withdrawn'])->count(),
            'active_awards'      => Award::where('status', 'active')->count(),
            'reports_due_soon'   => Report::where('status', 'pending')
                                        ->whereDate('due_date', '<=', now()->addDays(14))->count(),
            'overdue_reports'    => Report::where('status', 'pending')
                                        ->whereDate('due_date', '<', now())->count(),
        ];

        $recentCalls = FundingCall::with('donor')
            ->where('status', 'open')
            ->orderBy('deadline')
            ->limit(5)
            ->get();

        $activeAwards = Award::with(['proposal.leadPi', 'donor'])
            ->withHealth()
            ->where('status', 'active')
            ->limit(6)
            ->get();

        $upcomingDeadlines = ComplianceCalendar::with('award')
            ->whereIn('status', ['upcoming', 'overdue'])
            ->whereDate('due_date', '<=', now()->addDays(30))
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        return view('dashboard.index', compact('stats', 'recentCalls', 'activeAwards', 'upcomingDeadlines'));
    }

    private function researcherDashboard($user, ComplianceRiskEngine $riskEngine)
    {
        $awardsQuery = fn() => Award::whereHas('proposal', function ($q) use ($user) {
            $q->where('lead_pi_id', $user->id)
              ->orWhereHas('teamMembers', fn($t) => $t->where('user_id', $user->id));
        });

        $activeAwards = $awardsQuery()
            ->with(['proposal.leadPi', 'donor', 'reports', 'complianceCalendar', 'ethicsRenewals', 'budgets'])
            ->withHealth()
            ->where('status', 'active')
            ->get();

        $awardIds = $activeAwards->pluck('id');

        // What this researcher personally owes, and when.
        $myReports = Report::with('award')
            ->whereIn('award_id', $awardIds)
            ->whereIn('status', ['pending', 'rejected'])
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $myDeliverables = ComplianceCalendar::with('award')
            ->whereIn('award_id', $awardIds)
            ->whereIn('status', ['upcoming', 'overdue'])
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // Compliance flags, from the same engine the GMU sees.
        $flags = [];
        foreach ($activeAwards as $award) {
            foreach ($riskEngine->analyse($award) as $flag) {
                $flags[] = $flag + ['award' => $award->title, 'award_id' => $award->id];
            }
        }

        $stats = [
            'my_awards'        => $activeAwards->count(),
            'reports_due'      => $myReports->filter(fn($r) => $r->due_date && $r->due_date->lte(now()->addDays(30)))->count(),
            'overdue'          => $myReports->filter(fn($r) => $r->due_date && $r->due_date->lt(now()))->count(),
            'my_proposals'     => Proposal::where('lead_pi_id', $user->id)
                                    ->whereNotIn('status', ['awarded', 'declined', 'withdrawn'])->count(),
            'open_calls'       => FundingCall::where('status', 'open')->count(),
            'high_risk_flags'  => collect($flags)->where('level', 'high')->count(),
        ];

        $recentCalls = FundingCall::with('donor')
            ->where('status', 'open')
            ->orderBy('deadline')
            ->limit(5)
            ->get();

        return view('dashboard.researcher', compact(
            'stats', 'activeAwards', 'myReports', 'myDeliverables', 'flags', 'recentCalls'
        ));
    }
}
