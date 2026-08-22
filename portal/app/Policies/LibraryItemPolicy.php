<?php

namespace App\Policies;

use App\Models\LibraryItem;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * Reusable proposal text. Anyone may contribute; only approved text is
 * circulated, and only the Coordinator approves it.
 */
class LibraryItemPolicy
{
    use ChecksGmuRoles;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LibraryItem $item): bool
    {
        return $item->is_approved
            || $item->created_by === $user->id
            || $this->runsThePipeline($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, LibraryItem $item): bool
    {
        return $item->created_by === $user->id || $this->runsThePipeline($user);
    }

    public function delete(User $user, LibraryItem $item): bool
    {
        return $this->runsThePipeline($user);
    }

    public function approve(User $user, LibraryItem $item): bool
    {
        return $this->isCoordinator($user);
    }
}
