<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Shared guards for the GMU policies.
 *
 * Every policy denies outright when the account carries no role. Accounts are
 * provisioned by the Coordinator with a role attached; a role-less account is
 * either a leftover or a mistake, and must not see grant data (finding C4).
 */
trait ChecksGmuRoles
{
    /**
     * Runs before every ability on the policy.
     *
     * Returning false denies immediately; returning null hands the decision to
     * the individual ability method.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active || $user->role === null) {
            return false;
        }

        return null;
    }

    protected function isCoordinator(User $user): bool
    {
        return $user->hasRole('coordinator');
    }

    /** Coordinator or Planning Officer — the two roles that run the pipeline. */
    protected function runsThePipeline(User $user): bool
    {
        return $user->hasAnyRole(['coordinator', 'planning_officer']);
    }

    protected function isGmuStaff(User $user): bool
    {
        return $user->isGmuStaff();
    }
}
