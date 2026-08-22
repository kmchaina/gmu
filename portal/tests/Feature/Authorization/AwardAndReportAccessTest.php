<?php

namespace Tests\Feature\Authorization;

use App\Models\Award;
use App\Models\AwardBudget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Audit findings C4, H2 and H5 — award records, the money attached to them,
 * and the sign-off on donor reports.
 */
class AwardAndReportAccessTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    public function test_a_researcher_cannot_manufacture_an_award(): void
    {
        $pi       = $this->user('pi');
        $proposal = $this->proposal($pi, null, ['status' => 'approved']);

        $this->actingAs($pi)
            ->post(route('awards.store'), [
                'proposal_id'     => $proposal->id,
                'donor_id'        => $proposal->fundingCall->donor_id,
                'contract_number' => 'SELF-AWARD-1',
                'title'           => 'Self-created award',
                'start_date'      => now()->toDateString(),
                'end_date'        => now()->addYear()->toDateString(),
                'total_budget'    => 500000,
                'currency'        => 'USD',
                'status'          => 'active',
            ])
            ->assertForbidden();

        $this->assertNull(Award::where('contract_number', 'SELF-AWARD-1')->first());
    }

    public function test_a_researcher_cannot_amend_or_delete_their_own_award(): void
    {
        $pi    = $this->user('pi');
        $award = $this->award($this->proposal($pi));

        $this->actingAs($pi)->get(route('awards.edit', $award))->assertForbidden();
        $this->actingAs($pi)->delete(route('awards.destroy', $award))->assertForbidden();

        // …but they can read the award they are running.
        $this->actingAs($pi)->get(route('awards.show', $award))->assertOk();
    }

    public function test_a_researcher_cannot_see_another_teams_award(): void
    {
        $outsider = $this->user('pi');
        $award    = $this->award($this->proposal($this->user('pi')));

        $this->actingAs($outsider)->get(route('awards.show', $award))->assertForbidden();
    }

    public function test_only_finance_and_the_coordinator_touch_budget_lines(): void
    {
        $award   = $this->award();
        $payload = ['category' => 'personnel', 'allocated_amount' => 1000, 'spent_amount' => 0];

        $this->actingAs($this->user('pi'))
            ->post(route('awards.budgets.store', $award), $payload)->assertForbidden();

        $this->actingAs($this->user('legal_officer'))
            ->post(route('awards.budgets.store', $award), $payload)->assertForbidden();

        $this->actingAs($this->user('finance_officer'))
            ->post(route('awards.budgets.store', $award), $payload)->assertRedirect();

        $this->assertSame(1, $award->budgets()->count());
    }

    public function test_a_budget_line_cannot_be_reached_through_another_award(): void
    {
        $coordinator = $this->user('coordinator');
        $awardA      = $this->award();
        $awardB      = $this->award();

        $budgetOfB = AwardBudget::create([
            'award_id'         => $awardB->id,
            'category'         => 'equipment',
            'allocated_amount' => 5000,
            'spent_amount'     => 0,
            'currency'         => 'USD',
        ]);

        // Finding H5: routing scoped bindings make this a 404, not a deletion.
        $this->actingAs($coordinator)
            ->delete(route('awards.budgets.destroy', [$awardA, $budgetOfB]))
            ->assertNotFound();

        $this->assertNotNull(AwardBudget::find($budgetOfB->id));
    }

    public function test_the_submitter_cannot_approve_their_own_report(): void
    {
        $financeOfficer = $this->user('finance_officer');
        $award          = $this->award();
        $report         = $this->report($award, $financeOfficer);

        $payload = [
            'report_type' => 'financial',
            'title'       => 'Q1 financial report',
            'due_date'    => now()->addDays(14)->toDateString(),
            'status'      => 'approved',
        ];

        // Finding H2: verification is a second pair of eyes, always.
        $this->actingAs($financeOfficer)
            ->put(route('reports.update', $report), $payload)
            ->assertForbidden();

        $this->assertSame('submitted', $report->fresh()->status);

        $coordinator = $this->user('coordinator');
        $this->actingAs($coordinator)->put(route('reports.update', $report), $payload)->assertRedirect();

        $report->refresh();
        $this->assertSame('approved', $report->status);
        $this->assertSame($coordinator->id, $report->verified_by);
    }

    public function test_a_pi_cannot_approve_a_report_at_all(): void
    {
        $pi     = $this->user('pi');
        $award  = $this->award($this->proposal($pi));
        $report = $this->report($award, $pi);

        $this->actingAs($pi)
            ->put(route('reports.update', $report), [
                'report_type' => 'financial',
                'title'       => 'Q1 financial report',
                'due_date'    => now()->addDays(14)->toDateString(),
                'status'      => 'approved',
            ])
            ->assertForbidden();

        $this->assertNotSame('approved', $report->fresh()->status);
    }
}
