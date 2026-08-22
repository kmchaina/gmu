<?php

namespace App\Policies;

use App\Models\Donor;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * Donor records are reference data: widely readable, narrowly editable.
 * (Policy for the Donor model — App\Policies\DonorPolicyPolicy covers the
 * DonorPolicy model, which holds donor guideline documents.)
 */
class DonorPolicy
{
    use ChecksGmuRoles;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Donor $donor): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->runsThePipeline($user);
    }

    public function update(User $user, Donor $donor): bool
    {
        return $this->runsThePipeline($user);
    }

    public function delete(User $user, Donor $donor): bool
    {
        return $this->isCoordinator($user);
    }
}
