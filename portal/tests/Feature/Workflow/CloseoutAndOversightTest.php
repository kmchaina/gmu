<?php

namespace Tests\Feature\Workflow;

use App\Models\AwardCloseout;
use App\Models\Expenditure;
use App\Models\GmuNotification;
use App\Models\SubAward;
use App\Services\AuditReadinessScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Phase 3 — the framework gaps: Closeout & Lessons Learned (the eighth
 * Level-1 process), sub-awards, the expenditure ledger, and the audit
 * readiness score from slide 12 of the GMU concept.
 */
class CloseoutAndOversightTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    // ── Closeout ────────────────────────────────────────────────────────────

    public function test_closeout_tracks_progress_and_cannot_be_signed_off_early(): void
    {
        $coordinator = $this->user('coordinator');
        $award       = $this->award(null, ['end_date' => now()->subMonth()]);

        $this->actingAs($coordinator)->get(route('awards.closeout', $award))->assertOk();

        $this->actingAs($coordinator)->patch(route('awards.closeout.update', $award), [
            'steps'             => ['final_technical_report_at', 'final_financial_report_at'],
            'total_expenditure' => 480000,
            'unspent_balance'   => 20000,
        ])->assertRedirect();

        $closeout = AwardCloseout::where('award_id', $award->id)->first();
        $this->assertSame('in_progress', $closeout->status);
        $this->assertSame(25, $closeout->progress()); // 2 of 8 steps
        $this->assertNotNull($closeout->final_technical_report_at);

        // Signing off early must fail: the framework's steps are the point.
        $this->actingAs($coordinator)->post(route('awards.closeout.complete', $award))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotSame('completed', $closeout->fresh()->status);
        $this->assertSame('active', $award->fresh()->status);
    }

    public function test_a_complete_closeout_archives_the_award_and_tells_the_team(): void
    {
        $coordinator = $this->user('coordinator');
        $pi          = $this->user('pi');
        $award       = $this->award($this->proposal($pi), ['end_date' => now()->subMonth()]);

        $this->actingAs($coordinator)->patch(route('awards.closeout.update', $award), [
            'steps'              => array_keys(AwardCloseout::STEPS),
            'what_went_well'     => 'Field teams were in place before the first disbursement, so recruitment started on time.',
            'what_was_difficult' => 'Customs clearance for reagents delayed the laboratory work by two months.',
            'recommendations'    => 'Budget for a clearing agent and start the import paperwork at contract signature.',
            'archive_reference'  => 'GMU/ARCHIVE/2026/014',
        ])->assertRedirect();

        $this->actingAs($coordinator)->post(route('awards.closeout.complete', $award))->assertRedirect();

        $closeout = AwardCloseout::where('award_id', $award->id)->first();
        $this->assertSame('completed', $closeout->status);
        $this->assertSame(100, $closeout->progress());
        $this->assertTrue($closeout->hasLessons());
        $this->assertSame($coordinator->id, $closeout->completed_by);

        // Step 4 of the framework: the system archives the project.
        $this->assertSame('completed', $award->fresh()->status);
        $this->assertTrue(GmuNotification::where('user_id', $pi->id)->where('type', 'award_closed')->exists());
    }

    public function test_a_researcher_cannot_run_closeout(): void
    {
        $pi    = $this->user('pi');
        $award = $this->award($this->proposal($pi));

        // They may read their own award's closeout state…
        $this->actingAs($pi)->get(route('awards.closeout', $award))->assertOk();

        // …but not record it.
        $this->actingAs($pi)->patch(route('awards.closeout.update', $award), [
            'steps' => ['final_technical_report_at'],
        ])->assertForbidden();
    }

    // ── Sub-awards ──────────────────────────────────────────────────────────

    public function test_sub_awards_are_recorded_with_their_compliance(): void
    {
        $legal = $this->user('legal_officer');
        $award = $this->award(null, ['currency' => 'USD']);

        $this->actingAs($legal)->post(route('awards.sub-awards.store', $award), [
            'partner_name'        => 'Kilimanjaro Clinical Research Institute',
            'partner_type'        => 'institution',
            'country'             => 'Tanzania',
            'amount'              => 120000,
            'start_date'          => now()->toDateString(),
            'end_date'            => now()->addYear()->toDateString(),
            'status'              => 'active',
            'reporting_frequency' => 'quarterly',
        ])->assertRedirect();

        $subAward = SubAward::first();
        $this->assertSame('Kilimanjaro Clinical Research Institute', $subAward->partner_name);
        $this->assertSame('120000.00', (string) $subAward->base_amount);

        // Active with no due diligence recorded is a red flag, by design.
        $this->assertSame('red', $subAward->health());

        $subAward->update(['due_diligence_completed_on' => now(), 'last_monitored_on' => now()]);
        $this->assertSame('green', $subAward->fresh()->health());
    }

    public function test_a_researcher_cannot_record_a_sub_award(): void
    {
        $pi    = $this->user('pi');
        $award = $this->award($this->proposal($pi));

        $this->actingAs($pi)->post(route('awards.sub-awards.store', $award), [
            'partner_name'        => 'Some partner',
            'partner_type'        => 'ngo',
            'amount'              => 5000,
            'start_date'          => now()->toDateString(),
            'end_date'            => now()->addYear()->toDateString(),
            'status'              => 'active',
            'reporting_frequency' => 'quarterly',
        ])->assertForbidden();

        $this->assertSame(0, SubAward::count());
    }

    // ── Expenditure ledger ──────────────────────────────────────────────────

    public function test_the_ledger_drives_the_budget_line(): void
    {
        $finance = $this->user('finance_officer');
        $award   = $this->award();

        $line = $award->budgets()->create([
            'category'         => 'personnel',
            'allocated_amount' => 10000,
            'spent_amount'     => 0,
            'currency'         => $award->currency,
        ]);

        foreach ([1200.50, 800.25] as $amount) {
            $this->actingAs($finance)->post(route('awards.expenditures.store', $award), [
                'award_budget_id'  => $line->id,
                'transaction_date' => now()->subDays(3)->toDateString(),
                'description'      => 'Field team allowances',
                'reference'        => 'PV-' . random_int(1000, 9999),
                'amount'           => $amount,
            ])->assertRedirect();
        }

        $this->assertSame('2000.75', (string) $line->fresh()->spent_amount);
        $this->assertSame(2, Expenditure::count());

        // Removing an entry takes the budget line with it.
        $this->actingAs($finance)->delete(route('awards.expenditures.destroy', [$award, Expenditure::first()]))
            ->assertRedirect();

        $this->assertSame('800.25', (string) $line->fresh()->spent_amount);
    }

    public function test_expenditure_cannot_be_posted_to_another_awards_budget_line(): void
    {
        $finance = $this->user('finance_officer');
        $awardA  = $this->award();
        $awardB  = $this->award();

        $lineOfB = $awardB->budgets()->create([
            'category' => 'equipment', 'allocated_amount' => 5000, 'spent_amount' => 0, 'currency' => 'USD',
        ]);

        $this->actingAs($finance)->post(route('awards.expenditures.store', $awardA), [
            'award_budget_id'  => $lineOfB->id,
            'transaction_date' => now()->toDateString(),
            'description'      => 'Microscope',
            'amount'           => 4000,
        ])->assertSessionHas('error');

        $this->assertSame(0, Expenditure::count());
        $this->assertSame('0.00', (string) $lineOfB->fresh()->spent_amount);
    }

    // ── Audit readiness ─────────────────────────────────────────────────────

    public function test_the_readiness_score_reflects_what_an_auditor_would_ask(): void
    {
        $award = $this->award();
        $score = app(AuditReadinessScore::class);

        // A bare award: no ethics record, no budget, no monitoring, no contract.
        $bare = $score->for($award);
        $this->assertLessThan(50, $bare['score']);
        $this->assertNotEmpty($bare['gaps']);

        // Put the paperwork in place.
        $award->update(['signed_contract' => 'award-contracts/contract.pdf']);
        $award->ethicsRenewals()->create([
            'ethics_number' => 'NIMR/HQ/R.8a/Vol.IX/3001',
            'expiry_date'   => now()->addYear(),
            'renewal_status' => 'valid',
        ]);
        $line = $award->budgets()->create([
            'category' => 'personnel', 'allocated_amount' => 1000, 'spent_amount' => 500, 'currency' => 'USD',
        ]);
        $award->expenditures()->create([
            'award_budget_id'  => $line->id,
            'transaction_date' => now()->subMonth(),
            'description'      => 'Field allowances',
            'reference'        => 'PV-4471',
            'amount'           => 500,
            'currency'         => 'USD',
        ]);
        $award->monitoringVisits()->create([
            'conducted_by' => $this->user('planning_officer')->id,
            'visit_date'   => now()->subMonths(2),
            'findings'     => 'Records complete and reconciled to the ledger.',
        ]);

        $improved = $score->for($award->fresh());

        $this->assertGreaterThan($bare['score'], $improved['score']);
        $this->assertContains($improved['band'], ['ready', 'minor gaps']);
    }

    public function test_untraceable_spending_shows_up_in_the_score(): void
    {
        $award = $this->award();

        // Spend reported on the budget line with no ledger entries behind it.
        $award->budgets()->create([
            'category' => 'equipment', 'allocated_amount' => 20000, 'spent_amount' => 15000, 'currency' => 'USD',
        ]);

        $result = app(AuditReadinessScore::class)->for($award->fresh());

        $this->assertSame(0.0, $result['dimensions']['financial']['ratio']);
        $this->assertTrue(
            collect($result['gaps'])->contains(fn($gap) => str_contains($gap, 'no ledger entries')),
            'expected the untraceable expenditure to be named in the gaps'
        );
    }
}
