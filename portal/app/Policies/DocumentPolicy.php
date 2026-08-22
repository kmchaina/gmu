<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * The repository holds SOPs and templates alongside contracts and audit
 * responses. Reading follows the is_public flag and ownership; the flag now
 * governs the file itself, not just the listing (finding C6).
 */
class DocumentPolicy
{
    use ChecksGmuRoles;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return $document->is_public
            || $document->uploaded_by === $user->id
            || $this->isGmuStaff($user);
    }

    /** Downloading the file is the same decision as viewing the record. */
    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Document $document): bool
    {
        return $document->uploaded_by === $user->id || $this->isCoordinator($user);
    }

    public function delete(User $user, Document $document): bool
    {
        return $document->uploaded_by === $user->id || $this->isCoordinator($user);
    }
}
