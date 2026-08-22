<?php

namespace Tests\Feature\Workflow;

use App\Models\Award;
use App\Models\ComplianceCalendar;
use App\Models\GmuNotification;
use App\Models\Proposal;
use App\Models\Report;
use App\Services\ComplianceScheduleGenerator;
use App\Support\Days;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Audit findings M1, M3, M5, M6 — the award side: schedule generation, overdue
 * tracking, currency handling, and the numbers management reads.
 */
class AwardLifecycleTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    public function test_award_setup_generates_the_reporting_schedule(): void
    {
        $coordinator = $this->user('coordinator');
        $pi          = $this->user('pi');
        $proposal    = $this->proposal($pi, null, ['status' => 'approved']);

        $this->actingAs($coordinator)->post(route('awards.store'), [
            'proposal_id'         => $proposal->id,
            'donor_id'            => $proposal->fundingCall->donor_id,
            'contract_number'     => 'GMU-2026-014',
            'title'               => 'Vector surveillance in Kigoma',
            'start_date'          => now()->toDateString(),
            'end_date'            => now()->addYear()->toDateString(),
            'total_budget'        => 400000,
            'currency'            => 'USD',
            'status'              => 'active',
            'reporting_frequency' => 'quarterly',
        ])->assertRedirect();

        $award = Award::where('contract_number', 'GMU-2026-014')->first();
        $this->assertNotNull($award);

        // Finding M6: four quarters, a technical and a financial report each.
        $this->assertSame(8, Report::where('award_id', $award->id)->count());
        $this->assertSame(3, ComplianceCalendar::where('award_id', $award->id)->count());
        $this->assertTrue($award->schedule_generated);

        // Each report is due a month after the period it covers.
        $first = Report::where('award_id', $award->id)->orderBy('due_date')->first();
        $this->assertSame(30, Days::until($first->due_date) - Days::until($first->period_end));

        // Finding M4: the PI hears about the award.
        $this->assertTrue(GmuNotification::where('user_id', $pi->id)->where('type', 'award_created')->exists());
    }

    public function test_the_schedule_is_not_generated_twice(): void
    {
        $award     = $this->award();
        $generator = app(ComplianceScheduleGenerator::class);

        $first  = $generator->generate($award);
        $second = $generator->generate($award->fresh());

        $this->assertGreaterThan(0, $first['reports']);
        $this->assertSame(0, $second['reports']);
        $this->assertSame(0, $second['compliance']);
    }

    public function test_deliverables_that_slip_are_marked_overdue_and_the_office_is_told(): void
    {
        $coordinator = $this->user('coordinator');
        $award       = $this->award();

        $item = $award->complianceCalendar()->create([
            'deliverable_type' => 'technical_report',
            'title'            => 'Annual technical report',
            'due_date'         => now()->subDays(5)->toDateString(),
            'status'           => 'upcoming',
        ]);

        $this->artisan('gmu:mark-overdue')->assertSuccessful();

        // Finding M6: the "overdue" status existed and nothing ever set it.
        $this->assertSame('overdue', $item->fresh()->status);
        $this->assertTrue(
            GmuNotification::where('user_id', $coordinator->id)->where('type', 'compliance_overdue')->exists()
        );
    }

    public function test_amounts_are_converted_to_the_reporting_currency(): void
    {
        $award = $this->award(null, ['total_budget' => 1000, 'currency' => 'EUR']);

        // Finding M3: totals used to add euros and shillings together as if
        // they were the same unit.
        $this->assertSame('1090.00', (string) $award->base_total_budget);
        $this->assertEqualsWithDelta(1.09, (float) $award->exchange_rate, 0.000001);

        $budget = $award->budgets()->create([
            'category'         => 'personnel',
            'allocated_amount' => 500,
            'spent_amount'     => 100,
            'currency'         => 'EUR',
        ]);

        $this->assertSame('545.00', (string) $budget->base_allocated_amount);
        $this->assertSame('109.00', (string) $budget->base_spent_amount);
    }

    public function test_the_success_rate_counts_funded_against_decided(): void
    {
        $planning = $this->user('planning_officer');
        $call     = $this->fundingCall();

        // Two funded, one declined, one still in review, one withdrawn.
        $this->proposal($this->user('pi'), $call, ['status' => 'awarded']);
        $this->proposal($this->user('pi'), $call, ['status' => 'awarded']);
        $this->proposal($this->user('pi'), $call, ['status' => 'declined']);
        $this->proposal($this->user('pi'), $call, ['status' => 'internal_review']);
        $this->proposal($this->user('pi'), $call, ['status' => 'withdrawn']);

        // Finding M1: 2 of 3 decided = 66.7%, not 0% as the old formula gave.
        $this->assertSame(3, Proposal::decided()->count());
        $this->assertSame(2, Proposal::funded()->count());

        $this->actingAs($planning)
            ->get(route('analytics'))
            ->assertOk()
            ->assertSee('66.7%');
    }

    public function test_day_counts_are_whole_days(): void
    {
        // Finding M5: Carbon 3 returns floats, so messages read
        // "12.250000298727 day(s) overdue".
        $this->assertSame(12, Days::overdueBy(now()->subDays(12)->subHours(6)));
        $this->assertSame(30, Days::until(now()->addDays(30)->addHours(5)));
        $this->assertSame(0, Days::overdueBy(now()->addDay()));
        $this->assertSame('in 5 days', Days::describe(now()->addDays(5)));
    }
}
