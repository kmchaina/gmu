<?php

namespace Tests\Support;

use App\Models\Award;
use App\Models\Donor;
use App\Models\FundingCall;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;

/**
 * Builds the small object graph the authorization tests need: a donor, a call,
 * a proposal with a lead PI, and an award with a report.
 */
trait BuildsGrantFixtures
{
    protected function user(string $role, array $attributes = []): User
    {
        return User::factory()->role($role)->create($attributes);
    }

    protected function roleless(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role_id' => null]);
    }

    protected function donor(): Donor
    {
        return Donor::create(['name' => 'Wellcome Trust', 'acronym' => 'WT', 'type' => 'foundation']);
    }

    protected function fundingCall(?User $postedBy = null, array $attributes = []): FundingCall
    {
        $postedBy ??= $this->user('coordinator');

        return FundingCall::create($attributes + [
            'title'           => 'Malaria vector surveillance',
            'donor_id'        => $this->donor()->id,
            'posted_by'       => $postedBy->id,
            'theme'           => 'Malaria',
            'deadline'        => now()->addDays(30),
            'status'          => 'open',
            'currency'        => 'USD',
            'initiation_type' => 'gmu_posted',
        ]);
    }

    protected function proposal(User $leadPi, ?FundingCall $call = null, array $attributes = []): Proposal
    {
        $call ??= $this->fundingCall();

        return Proposal::create($attributes + [
            'funding_call_id' => $call->id,
            'lead_pi_id'      => $leadPi->id,
            'title'           => 'Vector surveillance in Kigoma',
            'abstract'        => 'A study of vector populations.',
            'status'          => 'concept',
            'currency'        => 'USD',
            'initiation_type' => 'gmu_initiated',
        ]);
    }

    protected function award(?Proposal $proposal = null, array $attributes = []): Award
    {
        $proposal ??= $this->proposal($this->user('pi'));

        return Award::create($attributes + [
            'proposal_id'     => $proposal->id,
            'donor_id'        => $proposal->fundingCall->donor_id,
            'contract_number' => 'GMU-' . fake()->unique()->numerify('####'),
            'title'           => $proposal->title,
            'start_date'      => now()->subMonth(),
            'end_date'        => now()->addYear(),
            'total_budget'    => 500000,
            'currency'        => 'USD',
            'status'          => 'active',
        ]);
    }

    protected function report(Award $award, User $submittedBy, array $attributes = []): Report
    {
        return Report::create($attributes + [
            'award_id'     => $award->id,
            'submitted_by' => $submittedBy->id,
            'report_type'  => 'financial',
            'title'        => 'Q1 financial report',
            'due_date'     => now()->addDays(14),
            'status'       => 'submitted',
        ]);
    }

    protected function seedRoles(): void
    {
        foreach (['coordinator', 'planning_officer', 'finance_officer', 'legal_officer', 'research_scientist', 'pi', 'secretary'] as $name) {
            Role::firstOrCreate(['name' => $name], ['display_name' => $name]);
        }
    }
}
