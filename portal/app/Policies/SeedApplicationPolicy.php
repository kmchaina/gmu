<?php

namespace App\Policies;

use App\Models\Seed\SeedApplication;
use App\Models\User;
use App\Support\SeedGrant;

/**
 * Who may see and change a seed grant application.
 * Reviewers reach applications only through their review page, never here.
 */
class SeedApplicationPolicy
{
    /** Statuses the DRCP and DG are entitled to see. */
    private const APPROVER_VISIBLE = [
        SeedGrant::RECOMMENDED, SeedGrant::NOT_RECOMMENDED, SeedGrant::WITH_DRCP, SeedGrant::WITH_DG,
        SeedGrant::APPROVED, SeedGrant::AGREEMENT_ISSUED, SeedGrant::AGREEMENT_ACCEPTED, SeedGrant::AWARDED,
    ];

    public function view(User $user, SeedApplication $application): bool
    {
        return $application->pi_id === $user->id
            || $user->can('manage-seed-grants')
            || ($user->can('approve-seed-grants') && in_array($application->status, self::APPROVER_VISIBLE, true));
    }

    public function update(User $user, SeedApplication $application): bool
    {
        return $application->pi_id === $user->id && $application->isEditableByApplicant();
    }

    public function manage(User $user, SeedApplication $application): bool
    {
        return $user->can('manage-seed-grants');
    }

    public function viewAgreement(User $user, SeedApplication $application): bool
    {
        return $application->agreement_issued_at !== null && $this->view($user, $application);
    }
}
