<?php

namespace App\Policies;

use App\Models\DonorPolicy as DonorPolicyModel;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * Donor guidelines, reporting and budget templates. Everyone writing a
 * proposal needs to read them; Legal and the GMU maintain them.
 */
class DonorPolicyPolicy
{
    use ChecksGmuRoles;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DonorPolicyModel $policy): bool
    {
        return true;
    }

    public function download(User $user, DonorPolicyModel $policy): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['coordinator', 'planning_officer', 'legal_officer']);
    }

    public function update(User $user, DonorPolicyModel $policy): bool
    {
        return $user->hasAnyRole(['coordinator', 'planning_officer', 'legal_officer']);
    }

    public function delete(User $user, DonorPolicyModel $policy): bool
    {
        return $this->isCoordinator($user);
    }
}
