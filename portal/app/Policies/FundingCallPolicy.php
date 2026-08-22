<?php

namespace App\Policies;

use App\Models\FundingCall;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * Calls are institution-wide information: everyone with a role may read them,
 * only the GMU may post, amend or withdraw them (finding C4).
 */
class FundingCallPolicy
{
    use ChecksGmuRoles;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FundingCall $call): bool
    {
        // Drafts are not circulated until the GMU opens them.
        return $call->status !== 'draft' || $this->runsThePipeline($user);
    }

    public function create(User $user): bool
    {
        return $this->runsThePipeline($user);
    }

    public function update(User $user, FundingCall $call): bool
    {
        return $this->runsThePipeline($user);
    }

    public function delete(User $user, FundingCall $call): bool
    {
        return $this->isCoordinator($user);
    }

    /** Researchers register interest; GMU staff do not compete for their own calls. */
    public function expressInterest(User $user, FundingCall $call): bool
    {
        return $user->isResearcher() && $call->status === 'open';
    }

    /** Choosing the team from the expressions of interest is a GMU decision. */
    public function selectTeam(User $user, FundingCall $call): bool
    {
        return $this->runsThePipeline($user);
    }

    /** Motivations and experience submitted by colleagues are not public reading. */
    public function viewInterests(User $user, FundingCall $call): bool
    {
        return $this->runsThePipeline($user);
    }
}
