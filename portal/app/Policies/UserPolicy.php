<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * Accounts and researcher profiles.
 *
 * Profiles are visible across the GMU because matching researchers to calls
 * depends on them — but conflict-of-interest disclosures are not (finding H3):
 * they name private financial interests and are seen only by their owner and
 * the Coordinator.
 */
class UserPolicy
{
    use ChecksGmuRoles;

    /** The admin user list. */
    public function viewAny(User $user): bool
    {
        return $this->isCoordinator($user);
    }

    public function view(User $user, User $subject): bool
    {
        return $this->isCoordinator($user) || $user->id === $subject->id;
    }

    public function create(User $user): bool
    {
        return $this->isCoordinator($user);
    }

    public function update(User $user, User $subject): bool
    {
        return $this->isCoordinator($user);
    }

    public function delete(User $user, User $subject): bool
    {
        return $this->isCoordinator($user) && $user->id !== $subject->id;
    }

    /** The researcher directory. */
    public function viewDirectory(User $user): bool
    {
        return $this->isGmuStaff($user);
    }

    public function viewProfile(User $user, User $subject): bool
    {
        return $this->isGmuStaff($user)
            || $user->id === $subject->id
            || $user->hasRole('research_scientist');
    }

    public function updateProfile(User $user, User $subject): bool
    {
        return $user->id === $subject->id || $this->runsThePipeline($user);
    }

    /** Conflict-of-interest disclosures — owner and Coordinator only. */
    public function viewFcoi(User $user, User $subject): bool
    {
        return $user->id === $subject->id || $this->isCoordinator($user);
    }

    public function submitFcoi(User $user, User $subject): bool
    {
        return $user->id === $subject->id || $this->isCoordinator($user);
    }
}
