<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'open_calls'        => \App\Models\FundingCall::where('status', 'open')->count(),
            'calls_closing_soon'=> \App\Models\FundingCall::where('status', 'open')->where('deadline', '<=', now()->addDays(30))->count(),
            'active_proposals'  => \App\Models\Proposal::whereNotIn('status', ['awarded', 'declined', 'withdrawn'])->count(),
            'active_awards'     => \App\Models\Award::where('status', 'active')->count(),
            'reports_due_soon'  => \App\Models\Report::where('status', 'pending')->where('due_date', '<=', now()->addDays(14))->count(),
            'overdue_reports'   => \App\Models\Report::where('status', 'pending')->where('due_date', '<', now())->count(),
        ];

        $recentCalls = \App\Models\FundingCall::with('donor')
            ->where('status', 'open')
            ->orderBy('deadline')
            ->limit(5)
            ->get();

        $activeAwards = \App\Models\Award::with(['proposal.leadPi', 'donor'])
            ->where('status', 'active')
            ->limit(6)
            ->get();

        $upcomingDeadlines = \App\Models\ComplianceCalendar::with('award')
            ->where('status', 'upcoming')
            ->where('due_date', '<=', now()->addDays(30))
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        if ($user->isPI()) {
            $recentCalls = \App\Models\FundingCall::with('donor')->where('status', 'open')->orderBy('deadline')->limit(5)->get();
            $activeAwards = \App\Models\Award::with(['proposal.leadPi', 'donor'])->whereHas('proposal', fn($q) => $q->where('lead_pi_id', $user->id))->where('status', 'active')->get();
        }

        return view('dashboard.index', compact('stats', 'recentCalls', 'activeAwards', 'upcomingDeadlines'));
    }
}
