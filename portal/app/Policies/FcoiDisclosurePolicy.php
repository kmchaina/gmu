<?php

namespace App\Policies;

use App\Models\FcoiDisclosure;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * Financial conflict-of-interest disclosures are confidential compliance
 * records: the person who made the disclosure and the Coordinator who reviews
 * it, nobody else (finding H3).
 */
class FcoiDisclosurePolicy
{
    use ChecksGmuRoles;

    public function view(User $user, FcoiDisclosure $disclosure): bool
    {
        return $disclosure->user_id === $user->id || $this->isCoordinator($user);
    }

    public function update(User $user, FcoiDisclosure $disclosure): bool
    {
        return $disclosure->user_id === $user->id || $this->isCoordinator($user);
    }

    /** Clearing a disclosure, with or without a management plan. */
    public function review(User $user, FcoiDisclosure $disclosure): bool
    {
        return $this->isCoordinator($user) && $disclosure->user_id !== $user->id;
    }
}
