<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * A proposal is confidential to its team and to the GMU officers handling it
 * (finding C4), and nobody reviews their own work (finding H2).
 */
class ProposalPolicy
{
    use ChecksGmuRoles;

    public function viewAny(User $user): bool
    {
        return true; // the listing itself is scoped to what the user may see
    }

    public function view(User $user, Proposal $proposal): bool
    {
        return $this->isGmuStaff($user)
            || $user->hasRole('research_scientist')   // reviews the technical track
            || $this->belongsToTeam($user, $proposal);
    }

    public function create(User $user): bool
    {
        return $this->runsThePipeline($user) || $user->isResearcher();
    }

    public function update(User $user, Proposal $proposal): bool
    {
        if ($this->runsThePipeline($user) || $proposal->assigned_officer_id === $user->id) {
            return true;
        }

        // The lead PI drafts their own proposal, but loses edit rights once it
        // enters internal review — after that, changes go through the GMU.
        return $proposal->lead_pi_id === $user->id
            && in_array($proposal->status, ['concept', 'under_development'], true);
    }

    public function delete(User $user, Proposal $proposal): bool
    {
        return $this->isCoordinator($user);
    }

    /**
     * Submit one of the three internal reviews.
     *
     * The user must own a review track, and must have no stake in the proposal:
     * a Research Scientist who is the lead PI cannot sign off their own
     * technical review (finding H2).
     */
    public function review(User $user, Proposal $proposal): bool
    {
        if ($user->reviewTrack() === null) {
            return false;
        }

        return ! $this->belongsToTeam($user, $proposal);
    }

    /**
     * Record an institutional decision at a given level (finding M9).
     *
     * The Directorate and the Director General each sign their own step; the
     * Coordinator may record a decision on their behalf, which the audit log
     * attributes to whoever was signed in.
     */
    public function approveAt(User $user, Proposal $proposal, string $level): bool
    {
        if (! $this->isReviewComplete($proposal)) {
            return false;
        }

        return match ($level) {
            'drcp' => $user->hasAnyRole(['drcp', 'coordinator']),
            'dg'   => $user->hasRole('dg'),
            default => false,
        };
    }

    private function isReviewComplete(Proposal $proposal): bool
    {
        return $proposal->isReviewComplete();
    }

    /** Attach concept notes, budgets, letters of support. */
    public function manageDocuments(User $user, Proposal $proposal): bool
    {
        return $this->update($user, $proposal);
    }

    private function belongsToTeam(User $user, Proposal $proposal): bool
    {
        if ($proposal->lead_pi_id === $user->id || $proposal->assigned_officer_id === $user->id) {
            return true;
        }

        return $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }
}
