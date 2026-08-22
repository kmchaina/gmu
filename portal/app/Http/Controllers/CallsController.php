<?php

namespace App\Http\Controllers;

use App\Models\FundingCall;
use App\Models\Donor;
use App\Models\CallInterest;
use App\Support\UploadRules;
use Illuminate\Http\Request;

class CallsController extends Controller
{
    public function index(Request $request)
    {
        $query = FundingCall::with('donor', 'postedBy')
            ->withCount('interests', 'proposals');

        if ($request->filter === 'closing_soon') {
            $query->where('status', 'open')->where('deadline', '<=', now()->addDays(30));
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('theme', 'like', "%{$request->search}%");
            });
        }

        if ($request->donor_id) {
            $query->where('donor_id', $request->donor_id);
        }

        $calls  = $query->orderBy('deadline')->paginate(15)->withQueryString();
        $donors = Donor::orderBy('name')->get();

        return view('calls.index', compact('calls', 'donors'));
    }

    public function create()
    {
        $donors = Donor::orderBy('name')->get();
        return view('calls.create', compact('donors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'donor_id'            => 'required|exists:donors,id',
            'description'         => 'nullable|string',
            'theme'               => 'nullable|string|max:255',
            'eligibility'         => 'nullable|string',
            'solicitation_number' => 'nullable|string|max:100',
            'estimated_budget'    => 'nullable|numeric|min:0',
            'currency'            => 'required|string|max:10',
            'open_date'           => 'nullable|date',
            'deadline'            => 'required|date|after:today',
            'initiation_type'     => 'required|in:gmu_posted,researcher_initiated',
            'status'              => 'required|in:draft,open,closed,archived',
            'call_document'       => UploadRules::office(),
        ]);

        $data['posted_by'] = auth()->id();

        if ($request->hasFile('call_document')) {
            $data['call_document'] = $request->file('call_document')
                ->store('call-documents', 'public');
        }

        FundingCall::create($data);

        return redirect()->route('calls.index')
            ->with('success', 'Funding call posted successfully.');
    }

    public function show(FundingCall $call)
    {
        $call->load('donor', 'postedBy', 'interests.user', 'proposals');

        $userInterest = null;
        if (auth()->user()->isPI() || auth()->user()->isResearchScientist()) {
            $userInterest = $call->interests->where('user_id', auth()->id())->first();
        }

        return view('calls.show', compact('call', 'userInterest'));
    }

    public function edit(FundingCall $call)
    {
        $donors = Donor::orderBy('name')->get();
        return view('calls.edit', compact('call', 'donors'));
    }

    public function update(Request $request, FundingCall $call)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'donor_id'            => 'required|exists:donors,id',
            'description'         => 'nullable|string',
            'theme'               => 'nullable|string|max:255',
            'eligibility'         => 'nullable|string',
            'solicitation_number' => 'nullable|string|max:100',
            'estimated_budget'    => 'nullable|numeric|min:0',
            'currency'            => 'required|string|max:10',
            'open_date'           => 'nullable|date',
            'deadline'            => 'required|date',
            'initiation_type'     => 'required|in:gmu_posted,researcher_initiated',
            'status'              => 'required|in:draft,open,closed,archived',
            'call_document'       => UploadRules::office(),
        ]);

        if ($request->hasFile('call_document')) {
            $data['call_document'] = $request->file('call_document')
                ->store('call-documents', 'public');
        }

        $call->update($data);

        return redirect()->route('calls.show', $call)
            ->with('success', 'Funding call updated successfully.');
    }

    public function destroy(FundingCall $call)
    {
        $call->delete();
        return redirect()->route('calls.index')
            ->with('success', 'Funding call removed.');
    }

    public function expressInterest(Request $request, FundingCall $call)
    {
        $request->validate([
            'motivation'           => 'required|string|min:50',
            'relevant_experience'  => 'required|string|min:30',
        ]);

        CallInterest::updateOrCreate(
            ['funding_call_id' => $call->id, 'user_id' => auth()->id()],
            [
                'motivation'          => $request->motivation,
                'relevant_experience' => $request->relevant_experience,
                'status'              => 'pending',
            ]
        );

        return redirect()->route('calls.show', $call)
            ->with('success', 'Your interest has been submitted. The GMU team will review and contact you.');
    }

    public function selectTeam(Request $request, FundingCall $call)
    {
        $request->validate([
            'selected_user_ids'   => 'required|array|min:1',
            'selected_user_ids.*' => 'exists:users,id',
            'gmu_notes'           => 'nullable|string',
        ]);

        $allInterests = $call->interests;

        foreach ($allInterests as $interest) {
            if (in_array($interest->user_id, $request->selected_user_ids)) {
                $interest->update([
                    'status'     => 'selected',
                    'gmu_notes'  => $request->gmu_notes,
                    'decided_at' => now(),
                    'decided_by' => auth()->id(),
                ]);
            } else {
                $interest->update([
                    'status'     => 'not_selected',
                    'decided_at' => now(),
                    'decided_by' => auth()->id(),
                ]);
            }
        }

        return redirect()->route('calls.show', $call)
            ->with('success', 'Team selection saved. Researchers have been notified.');
    }
}
