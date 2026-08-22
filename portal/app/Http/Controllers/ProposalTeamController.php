<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\ProposalTeamMember;
use App\Services\Notifier;
use Illuminate\Http\Request;

/**
 * The research team on a proposal (audit finding M7).
 *
 * proposal_team_members had a table, a model and a relationship the proposal
 * page rendered — but nothing could ever add a row, so every proposal showed
 * its lead PI and no one else.
 */
class ProposalTeamController extends Controller
{
    public function store(Request $request, Proposal $proposal)
    {
        $this->authorize('manageDocuments', $proposal);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:lead_pi,co_investigator,collaborator',
        ]);

        if ((int) $data['user_id'] === (int) $proposal->lead_pi_id) {
            return back()->with('error', 'That person is already the lead PI on this proposal.');
        }

        if ($proposal->teamMembers()->where('user_id', $data['user_id'])->exists()) {
            return back()->with('error', 'That person is already on the team.');
        }

        $member = $proposal->teamMembers()->create($data);

        app(Notifier::class)->send(
            $member->user,
            'team_added',
            'Added to a proposal team',
            sprintf('You have been added to "%s" as %s.', $proposal->title, str_replace('_', ' ', $member->role)),
            $proposal
        );

        return back()->with('success', 'Team member added.');
    }

    public function destroy(Proposal $proposal, ProposalTeamMember $member)
    {
        $this->authorize('manageDocuments', $proposal);

        $member->delete();

        return back()->with('success', 'Team member removed.');
    }
}
