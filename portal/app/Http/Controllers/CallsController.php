<?php

namespace App\Http\Controllers;

use App\Models\FundingCall;
use App\Models\Donor;
use App\Models\CallInterest;
use App\Support\UploadRules;
use App\Support\GrantFiles;
use App\Services\Notifier;
use Illuminate\Http\Request;

class CallsController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', FundingCall::class);

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
        $this->authorize('create', FundingCall::class);

        $donors = Donor::orderBy('name')->get();
        return view('calls.create', compact('donors'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', FundingCall::class);

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
            $data['call_document'] = GrantFiles::store($request->file('call_document'), 'call-documents');
        }

        $call = FundingCall::create($data);

        // Researchers only hear about a call once it is actually open.
        if ($call->status === 'open') {
            app(Notifier::class)->callPosted($call->load('donor'));
        }

        return redirect()->route('calls.index')
            ->with('success', 'Funding call posted successfully.');
    }

    public function show(FundingCall $call)
    {
        $this->authorize('view', $call);

        $call->load('donor', 'postedBy', 'proposals');

        // Expressions of interest name colleagues and their motivations; only
        // the GMU sees the full list, everyone else sees their own.
        $canSeeInterests = auth()->user()->can('viewInterests', $call);
        $call->setRelation('interests', $canSeeInterests
            ? $call->interests()->with('user')->get()
            : $call->interests()->with('user')->where('user_id', auth()->id())->get());

        $userInterest = $call->interests->firstWhere('user_id', auth()->id());

        return view('calls.show', compact('call', 'userInterest', 'canSeeInterests'));
    }

    public function edit(FundingCall $call)
    {
        $this->authorize('update', $call);

        $donors = Donor::orderBy('name')->get();
        return view('calls.edit', compact('call', 'donors'));
    }

    public function update(Request $request, FundingCall $call)
    {
        $this->authorize('update', $call);

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
            $data['call_document'] = GrantFiles::replace($request->file('call_document'), 'call-documents', $call->call_document);
        }

        $call->update($data);

        return redirect()->route('calls.show', $call)
            ->with('success', 'Funding call updated successfully.');
    }

    public function destroy(FundingCall $call)
    {
        $this->authorize('delete', $call);

        $call->delete();
        return redirect()->route('calls.index')
            ->with('success', 'Funding call removed.');
    }

    /** The call document itself — readable by anyone who may see the call. */
    public function downloadDocument(FundingCall $call)
    {
        $this->authorize('view', $call);

        return GrantFiles::download($call->call_document);
    }

    public function expressInterest(Request $request, FundingCall $call)
    {
        $this->authorize('expressInterest', $call);

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
        $this->authorize('selectTeam', $call);

        $request->validate([
            'selected_user_ids'   => 'required|array|min:1',
            'selected_user_ids.*' => 'exists:users,id',
            'gmu_notes'           => 'nullable|string',
        ]);

        $notifier = app(Notifier::class);
        $notified = 0;

        foreach ($call->interests as $interest) {
            $interest->update(in_array($interest->user_id, $request->selected_user_ids)
                ? [
                    'status'     => 'selected',
                    'gmu_notes'  => $request->gmu_notes,
                    'decided_at' => now(),
                    'decided_by' => auth()->id(),
                ]
                : [
                    'status'     => 'not_selected',
                    'decided_at' => now(),
                    'decided_by' => auth()->id(),
                ]);

            $interest->setRelation('fundingCall', $call);
            $notified += $notifier->interestDecision($interest);
        }

        return redirect()->route('calls.show', $call)
            ->with('success', "Team selection saved. {$notified} researcher(s) notified.");
    }
}
