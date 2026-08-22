<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\FundingCall;
use App\Models\ProposalReview;
use App\Models\User;
use Illuminate\Http\Request;

class ProposalsController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Proposal::with('fundingCall.donor', 'leadPi', 'assignedOfficer')
            ->withCount('reviews');

        if ($user->isPI() || $user->isResearchScientist()) {
            $query->where('lead_pi_id', $user->id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $proposals = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('proposals.index', compact('proposals'));
    }

    public function create(Request $request)
    {
        $calls = FundingCall::where('status', 'open')->orderBy('deadline')->get();
        $pis   = User::whereHas('role', fn($q) => $q->whereIn('name', ['pi', 'research_scientist']))
            ->where('is_active', true)->orderBy('name')->get();

        $selectedCall = $request->funding_call_id
            ? FundingCall::find($request->funding_call_id)
            : null;

        return view('proposals.create', compact('calls', 'pis', 'selectedCall'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'funding_call_id'       => 'required|exists:funding_calls,id',
            'lead_pi_id'            => 'required|exists:users,id',
            'title'                 => 'required|string|max:255',
            'abstract'              => 'nullable|string',
            'initiation_type'       => 'required|in:gmu_initiated,researcher_initiated',
            'status'                => 'required|in:concept,under_development,internal_review,approved,submitted,awarded,declined,withdrawn',
            'target_submission_date'=> 'nullable|date',
            'requested_budget'      => 'nullable|numeric|min:0',
            'currency'              => 'required|string|max:10',
        ]);

        $data['assigned_officer_id'] = auth()->id();

        $proposal = Proposal::create($data);

        $this->initializeReviews($proposal);

        return redirect()->route('proposals.show', $proposal)
            ->with('success', 'Proposal registered successfully.');
    }

    public function show(Proposal $proposal)
    {
        $proposal->load('fundingCall.donor', 'leadPi', 'assignedOfficer', 'teamMembers.user', 'reviews.reviewer', 'documents', 'award');

        $user = auth()->user();
        $canReview = $user->hasAnyRole(['coordinator', 'research_scientist', 'legal_officer', 'finance_officer']);

        $userReview = null;
        if ($canReview) {
            $reviewTypeMap = [
                'research_scientist' => 'technical',
                'legal_officer'      => 'compliance',
                'finance_officer'    => 'budget',
                'coordinator'        => null,
            ];
            $reviewType = $reviewTypeMap[$user->role?->name] ?? null;
            if ($reviewType) {
                $userReview = $proposal->reviews->where('review_type', $reviewType)->first();
            }
        }

        return view('proposals.show', compact('proposal', 'canReview', 'userReview'));
    }

    public function edit(Proposal $proposal)
    {
        $calls = FundingCall::where('status', 'open')->orWhere('id', $proposal->funding_call_id)->orderBy('deadline')->get();
        $pis   = User::whereHas('role', fn($q) => $q->whereIn('name', ['pi', 'research_scientist']))
            ->where('is_active', true)->orderBy('name')->get();
        $officers = User::whereHas('role', fn($q) => $q->whereIn('name', ['coordinator', 'planning_officer']))
            ->where('is_active', true)->orderBy('name')->get();

        return view('proposals.edit', compact('proposal', 'calls', 'pis', 'officers'));
    }

    public function update(Request $request, Proposal $proposal)
    {
        $data = $request->validate([
            'funding_call_id'       => 'required|exists:funding_calls,id',
            'lead_pi_id'            => 'required|exists:users,id',
            'title'                 => 'required|string|max:255',
            'abstract'              => 'nullable|string',
            'initiation_type'       => 'required|in:gmu_initiated,researcher_initiated',
            'status'                => 'required|in:concept,under_development,internal_review,approved,submitted,awarded,declined,withdrawn',
            'target_submission_date'=> 'nullable|date',
            'submitted_at'          => 'nullable|date',
            'outcome_date'          => 'nullable|date',
            'outcome_notes'         => 'nullable|string',
            'requested_budget'      => 'nullable|numeric|min:0',
            'currency'              => 'required|string|max:10',
            'assigned_officer_id'   => 'nullable|exists:users,id',
        ]);

        $proposal->update($data);

        return redirect()->route('proposals.show', $proposal)
            ->with('success', 'Proposal updated successfully.');
    }

    public function destroy(Proposal $proposal)
    {
        $proposal->delete();
        return redirect()->route('proposals.index')
            ->with('success', 'Proposal removed.');
    }

    public function submitReview(Request $request, Proposal $proposal)
    {
        $user = auth()->user();

        $reviewTypeMap = [
            'research_scientist' => 'technical',
            'legal_officer'      => 'compliance',
            'finance_officer'    => 'budget',
        ];

        $reviewType = $reviewTypeMap[$user->role?->name] ?? null;

        if (!$reviewType) {
            abort(403, 'You are not assigned a review role for proposals.');
        }

        $request->validate([
            'status'   => 'required|in:approved,rejected,needs_revision',
            'comments' => 'required|string|min:20',
            'score'    => 'nullable|integer|min:1|max:10',
        ]);

        ProposalReview::updateOrCreate(
            ['proposal_id' => $proposal->id, 'review_type' => $reviewType],
            [
                'reviewer_id' => $user->id,
                'status'      => $request->status,
                'comments'    => $request->comments,
                'score'       => $request->score,
                'reviewed_at' => now(),
            ]
        );

        if ($proposal->isReviewComplete()) {
            $proposal->update(['status' => 'approved']);
        } elseif ($proposal->status === 'concept' || $proposal->status === 'under_development') {
            $proposal->update(['status' => 'internal_review']);
        }

        return redirect()->route('proposals.show', $proposal)
            ->with('success', 'Review submitted successfully.');
    }

    private function initializeReviews(Proposal $proposal): void
    {
        foreach (['technical', 'compliance', 'budget'] as $type) {
            ProposalReview::firstOrCreate(
                ['proposal_id' => $proposal->id, 'review_type' => $type],
                ['reviewer_id' => auth()->id(), 'status' => 'pending']
            );
        }
    }
}
