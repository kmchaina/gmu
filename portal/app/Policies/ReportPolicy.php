<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * Donor reporting. The PI submits, the GMU verifies — and never the same
 * person for the same report (finding H2).
 */
class ReportPolicy
{
    use ChecksGmuRoles;

    public function viewAny(User $user): bool
    {
        return true; // scoped in the query to the user's own awards for researchers
    }

    public function view(User $user, Report $report): bool
    {
        return $this->isGmuStaff($user) || $this->leadsTheAward($user, $report);
    }

    public function create(User $user): bool
    {
        return $this->isGmuStaff($user) || $user->isResearcher();
    }

    public function update(User $user, Report $report): bool
    {
        if ($this->isGmuStaff($user)) {
            return true;
        }

        // A PI may keep working on their own report until the GMU takes it over.
        return $this->leadsTheAward($user, $report)
            && in_array($report->status, ['pending', 'rejected'], true);
    }

    /**
     * Mark a report approved — the institutional sign-off that it may go to the
     * donor. Restricted to the officers who own verification, and never
     * available to the person who submitted it.
     */
    public function verify(User $user, Report $report): bool
    {
        if (! $user->hasAnyRole(['coordinator', 'finance_officer'])) {
            return false;
        }

        return $report->submitted_by !== $user->id;
    }

    public function delete(User $user, Report $report): bool
    {
        return $this->isCoordinator($user);
    }

    private function leadsTheAward(User $user, Report $report): bool
    {
        $proposal = $report->award?->proposal;

        if ($proposal === null) {
            return false;
        }

        return $proposal->lead_pi_id === $user->id
            || $proposal->teamMembers()->where('user_id', $user->id)->exists();
    }
}
