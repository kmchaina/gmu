<?php

namespace App\Http\Controllers;

use App\Models\FcoiDisclosure;
use App\Models\ResearcherProfile;
use App\Models\User;
use Illuminate\Http\Request;

class ResearcherProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Researchers go straight to their own profile; the directory itself is
        // for the officers who match people to calls.
        if (! $user->can('viewDirectory', User::class)) {
            return redirect()->route('researchers.show', $user);
        }

        // GMU staff see the full list
        $researchers = User::whereHas('role', fn($q) => $q->whereIn('name', ['pi', 'research_scientist']))
            ->where('is_active', true)
            ->with('researcherProfile', 'role')
            ->orderBy('name')
            ->paginate(24);

        return view('researchers.index', compact('researchers'));
    }

    /**
     * A researcher's profile.
     *
     * Conflict-of-interest disclosures name private financial interests. They
     * are shown to the person who made them and to the Coordinator who reviews
     * them, and to nobody else (finding H3).
     */
    public function show(User $user)
    {
        $this->authorize('viewProfile', $user);

        $profile   = $user->researcherProfile;
        $proposals = $user->proposals()->with('fundingCall', 'award')->latest()->get();

        $canSeeFcoi = auth()->user()->can('viewFcoi', $user);
        $fcois      = $canSeeFcoi
            ? $user->fcoiDisclosures()->with('proposal')->orderByDesc('disclosure_year')->get()
            : collect();

        $canEdit = auth()->user()->can('updateProfile', $user);

        return view('researchers.show', compact('user', 'profile', 'proposals', 'fcois', 'canEdit', 'canSeeFcoi'));
    }

    public function edit(User $user)
    {
        $this->authorizeEdit($user);
        $profile = $user->researcherProfile ?? new ResearcherProfile(['user_id' => $user->id]);
        return view('researchers.edit', compact('user', 'profile'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeEdit($user);

        $data = $request->validate([
            'title'            => 'nullable|string|max:20',
            'position'         => 'nullable|string|max:255',
            'department'       => 'nullable|string|max:255',
            'research_center'  => 'nullable|string|max:255',
            'bio'              => 'nullable|string|max:3000',
            'expertise_areas'  => 'nullable|string',   // comma-separated
            'phone'            => 'nullable|string|max:30',
            'linkedin_url'     => 'nullable|url|max:255',
            'researchgate_url' => 'nullable|url|max:255',
            'orcid_id'         => ['nullable','regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
        ]);

        // Parse expertise as array
        $data['expertise_areas'] = array_filter(
            array_map('trim', explode(',', $data['expertise_areas'] ?? ''))
        );

        // Handle qualifications JSON submitted as repeater fields
        $quals = [];
        foreach ($request->input('qual_degree', []) as $i => $degree) {
            if (filled($degree)) {
                $quals[] = [
                    'degree'      => $degree,
                    'field'       => $request->input("qual_field.{$i}"),
                    'institution' => $request->input("qual_institution.{$i}"),
                    'year'        => $request->input("qual_year.{$i}"),
                ];
            }
        }
        $data['qualifications'] = $quals ?: null;

        // Handle publications JSON as repeater fields
        $pubs = [];
        foreach ($request->input('pub_title', []) as $i => $title) {
            if (filled($title)) {
                $pubs[] = [
                    'title'   => $title,
                    'journal' => $request->input("pub_journal.{$i}"),
                    'year'    => $request->input("pub_year.{$i}"),
                    'doi'     => $request->input("pub_doi.{$i}"),
                ];
            }
        }
        $data['publications'] = $pubs ?: null;

        $user->researcherProfile()->updateOrCreate(['user_id' => $user->id], $data);

        return redirect()->route('researchers.show', $user)
            ->with('success', 'Profile updated successfully.');
    }

    // FCOI Disclosure store
    public function storeFcoi(Request $request, User $user)
    {
        $this->authorize('submitFcoi', $user);

        $data = $request->validate([
            'disclosure_year'      => 'required|integer|min:2000|max:2100',
            'proposal_id'          => 'nullable|exists:proposals,id',
            'has_conflict'         => 'required|boolean',
            'conflict_description' => 'required_if:has_conflict,1|nullable|string',
            'management_plan'      => 'nullable|string',
        ]);

        $data['user_id'] = $user->id;
        $data['status']  = 'submitted';

        FcoiDisclosure::updateOrCreate(
            [
                'user_id'          => $user->id,
                'proposal_id'      => $data['proposal_id'] ?? null,
                'disclosure_year'  => $data['disclosure_year'],
            ],
            $data
        );

        return redirect()->route('researchers.show', $user)
            ->with('success', 'FCOI disclosure submitted.');
    }

    // Coordinator reviews and clears a disclosure
    public function reviewFcoi(Request $request, FcoiDisclosure $disclosure)
    {
        $this->authorize('review', $disclosure);

        $request->validate([
            'status'         => 'required|in:reviewed,cleared',
            'reviewer_notes' => 'nullable|string',
        ]);

        $disclosure->update([
            'status'                    => $request->status,
            'reviewer_notes'            => $request->reviewer_notes,
            'management_plan_approved'  => $request->status === 'cleared',
            'approved_by'               => auth()->id(),
            'approved_at'               => now(),
        ]);

        return back()->with('success', 'FCOI disclosure ' . $request->status . '.');
    }

    private function authorizeEdit(User $user): void
    {
        $this->authorize('updateProfile', $user);
    }
}
