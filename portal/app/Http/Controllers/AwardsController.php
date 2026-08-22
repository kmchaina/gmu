<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Donor;
use App\Models\Proposal;
use App\Support\UploadRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AwardsController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Award::with('proposal.leadPi', 'donor')->withCount('reports');

        if ($user->isPI() || $user->isResearchScientist()) {
            $query->whereHas('proposal', fn($q) => $q->where('lead_pi_id', $user->id));
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
            'kickoff_date'    => 'nullable|date',
            'notes'           => 'nullable|string',
            'signed_contract' => UploadRules::office(),
        ]);

        if ($request->hasFile('signed_contract')) {
            $data['signed_contract'] = $request->file('signed_contract')
                ->store('award-contracts', 'public');
        }

        $award = Award::create($data);

        Proposal::find($request->proposal_id)?->update(['status' => 'awarded']);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Award created successfully.');
    }

    public function show(Award $award)
    {
        $award->load('proposal.leadPi', 'donor', 'budgets', 'reports', 'complianceCalendar', 'monitoringVisits', 'ethicsRenewals');

        return view('awards.show', compact('award'));
    }

    public function edit(Award $award)
    {
        $donors = Donor::orderBy('name')->get();
        return view('awards.edit', compact('award', 'donors'));
    }

    public function update(Request $request, Award $award)
    {
        $data = $request->validate([
            'contract_number' => 'required|string|max:100|unique:awards,contract_number,' . $award->id,
            'title'           => 'required|string|max:255',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after:start_date',
            'total_budget'    => 'required|numeric|min:0',
            'currency'        => 'required|string|max:10',
            'status'          => 'required|in:active,completed,suspended,terminated',
            'kickoff_date'    => 'nullable|date',
            'notes'           => 'nullable|string',
            'signed_contract' => UploadRules::office(),
        ]);

        if ($request->hasFile('signed_contract')) {
            $data['signed_contract'] = $request->file('signed_contract')
                ->store('award-contracts', 'public');
        }

        $award->update($data);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Award updated successfully.');
    }

    public function destroy(Award $award)
    {
        $award->delete();
        return redirect()->route('awards.index')
            ->with('success', 'Award removed.');
    }
}
