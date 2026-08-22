<?php

namespace App\Policies;

use App\Models\Award;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * Awards carry the contractual and financial record. Researchers see their own
 * grants; creating and amending them is GMU work, and the sub-records follow
 * the officer who owns that part of the process (findings C4, H2).
 */
class AwardPolicy
{
    use ChecksGmuRoles;

    public function viewAny(User $user): bool
    {
        return true; // the listing is scoped to the user's own awards for researchers
    }

    public function view(User $user, Award $award): bool
    {
        return $this->isGmuStaff($user) || $this->isAwardTeam($user, $award);
    }

    /** Award setup follows contract review — Coordinator and Legal own it. */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['coordinator', 'legal_officer']);
    }

    public function update(User $user, Award $award): bool
    {
        return $user->hasAnyRole(['coordinator', 'legal_officer']);
    }

    public function delete(User $user, Award $award): bool
    {
        return $this->isCoordinator($user);
    }

    /** Budget lines and expenditure are Finance's record. */
    public function manageBudget(User $user, Award $award): bool
    {
        return $user->hasAnyRole(['coordinator', 'finance_officer']);
    }

    /** Deliverable deadlines are maintained by the GMU office. */
    public function manageCompliance(User $user, Award $award): bool
    {
        return $user->hasAnyRole(['coordinator', 'planning_officer', 'secretary']);
    }

    /** Protocol approvals and renewals sit with Legal and the GMU office. */
    public function manageEthics(User $user, Award $award): bool
    {
        return $user->hasAnyRole(['coordinator', 'planning_officer', 'legal_officer']);
    }

    /** Monitoring visits are conducted by GMU officers. */
    public function manageMonitoring(User $user, Award $award): bool
    {
        return $user->hasAnyRole(['coordinator', 'planning_officer', 'research_scientist']);
    }

    private function isAwardTeam(User $user, Award $award): bool
    {
        $proposal = $award->proposal;

        if ($proposal === null) {
            return false;
        }

        return $proposal->lead_pi_id === $user->id
            || $proposal->assigned_officer_id === $user->id
            || $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }
}
