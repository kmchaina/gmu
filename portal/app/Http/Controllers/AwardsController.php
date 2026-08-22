<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Donor;
use App\Models\Proposal;
use App\Support\UploadRules;
use App\Support\GrantFiles;
use App\Services\AuditReadinessScore;
use App\Services\ComplianceScheduleGenerator;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AwardsController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', Award::class);

        $user  = auth()->user();
        $query = Award::with('proposal.leadPi', 'donor')->withCount('reports')->withHealth();

        // Researchers see the grants they are on; GMU officers see the portfolio.
        if (! $user->isGmuStaff()) {
            $query->whereHas('proposal', function ($q) use ($user) {
                $q->where('lead_pi_id', $user->id)
                  ->orWhereHas('teamMembers', fn($t) => $t->where('user_id', $user->id));
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->donor_id) {
            $query->where('donor_id', $request->donor_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('contract_number', 'like', "%{$request->search}%");
            });
        }

        $awards  = $query->orderBy('end_date')->paginate(15)->withQueryString();
        $donors  = Donor::orderBy('name')->get();

        return view('awards.index', compact('awards', 'donors'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Award::class);

        $donors   = Donor::orderBy('name')->get();
        $proposals = Proposal::with('leadPi', 'fundingCall.donor')
            ->whereIn('status', ['approved', 'submitted'])
            ->whereDoesntHave('award')
            ->orderBy('created_at', 'desc')
            ->get();

        $selectedProposal = $request->proposal_id
            ? Proposal::with('fundingCall.donor', 'leadPi')->find($request->proposal_id)
            : null;

        return view('awards.create', compact('donors', 'proposals', 'selectedProposal'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Award::class);

        $data = $request->validate([
            'proposal_id'     => 'required|exists:proposals,id|unique:awards,proposal_id',
            'donor_id'        => 'required|exists:donors,id',
            'contract_number' => 'required|string|max:100|unique:awards,contract_number',
            'title'           => 'required|string|max:255',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after:start_date',
            'total_budget'    => 'required|numeric|min:0',
            'currency'        => 'required|string|max:10',
            'status'          => 'required|in:active,completed,suspended,terminated',
            'reporting_frequency' => 'required|in:monthly,quarterly,semi_annual,annual',
            'kickoff_date'    => 'nullable|date',
            'notes'           => 'nullable|string',
            'signed_contract' => UploadRules::office(),
        ]);

        if ($request->hasFile('signed_contract')) {
            $data['signed_contract'] = GrantFiles::store($request->file('signed_contract'), 'award-contracts');
        }

        $award = Award::create($data);

        Proposal::find($request->proposal_id)?->update(['status' => 'awarded']);

        // Finding M6: step B6 of the business process framework — the compliance
        // calendar is generated when the award is set up, not typed in by hand.
        $schedule = app(ComplianceScheduleGenerator::class)->generate($award);

        app(Notifier::class)->awardCreated($award->load('proposal.leadPi', 'proposal.assignedOfficer'));

        return redirect()->route('awards.show', $award)->with(
            'success',
            sprintf(
                'Award created. %d reporting deadline(s) and %d compliance milestone(s) added to the calendar.',
                $schedule['reports'],
                $schedule['compliance']
            )
        );
    }

    public function show(Award $award)
    {
        $this->authorize('view', $award);

        $award->load([
            'proposal.leadPi', 'donor', 'budgets', 'reports', 'complianceCalendar',
            'monitoringVisits', 'ethicsRenewals', 'subAwards', 'closeout',
            'expenditures.budgetLine', 'expenditures.recordedBy',
        ]);

        $readiness = app(AuditReadinessScore::class)->for($award);

        return view('awards.show', compact('award', 'readiness'));
    }

    public function edit(Award $award)
    {
        $this->authorize('update', $award);

        $donors = Donor::orderBy('name')->get();
        return view('awards.edit', compact('award', 'donors'));
    }

    public function update(Request $request, Award $award)
    {
        $this->authorize('update', $award);

        $data = $request->validate([
            'contract_number' => 'required|string|max:100|unique:awards,contract_number,' . $award->id,
            'title'           => 'required|string|max:255',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after:start_date',
            'total_budget'    => 'required|numeric|min:0',
            'currency'        => 'required|string|max:10',
            'status'          => 'required|in:active,completed,suspended,terminated',
            'reporting_frequency' => 'required|in:monthly,quarterly,semi_annual,annual',
            'kickoff_date'    => 'nullable|date',
            'notes'           => 'nullable|string',
            'signed_contract' => UploadRules::office(),
        ]);

        if ($request->hasFile('signed_contract')) {
            $data['signed_contract'] = GrantFiles::replace($request->file('signed_contract'), 'award-contracts', $award->signed_contract);
        }

        $award->update($data);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Award updated successfully.');
    }

    /** The signed contract — the award team and GMU officers only. */
    public function downloadContract(Award $award)
    {
        $this->authorize('view', $award);

        return GrantFiles::download($award->signed_contract);
    }

    public function destroy(Award $award)
    {
        $this->authorize('delete', $award);

        $award->delete();
        return redirect()->route('awards.index')
            ->with('success', 'Award removed.');
    }
}
