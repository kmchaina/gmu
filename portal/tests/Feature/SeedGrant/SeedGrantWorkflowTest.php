<?php

namespace Tests\Feature\SeedGrant;

use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Models\Seed\SeedReviewerAssignment;
use App\Models\User;
use App\Support\SeedGrant as S;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * The seed grant from concept note to signed agreement, driven through the
 * same routes people use, with the call document's rules checked on the way.
 */
class SeedGrantWorkflowTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    private SeedCall $call;
    private User $pi;
    private User $gmu;
    private User $reviewerA;
    private User $reviewerB;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gmu.external_grants' => false]);
        Storage::fake('local');

        $this->call = SeedCall::create([
            'title' => 'Early-Career Researchers’ Implementation Science Seed Grant', 'code' => '2627', 'fiscal_year' => '2026/27',
            'min_amount' => 2_000_000, 'max_amount' => 10_000_000, 'envelope' => 50_000_000, 'duration_months' => 12,
            'equipment_cap_percent' => 20, 'status' => 'open',
        ]);

        $this->pi = $this->user('pi', ['name' => 'Neema Mushi', 'research_center' => 'Mwanza Centre',
            'employment_type' => 'permanent', 'first_appointment_date' => now()->subYears(3)]);
        $this->gmu = $this->user('coordinator');
        $this->reviewerA = $this->user('research_scientist', ['name' => 'Peter Mboya']);
        $this->reviewerB = $this->user('research_scientist', ['name' => 'Sara Kimaro']);
        $this->user('drcp');
        $this->user('dg');
    }

    private function words(int $n): string
    {
        return trim(str_repeat('implementation ', $n));
    }

    private function conceptForm(array $overrides = []): array
    {
        $sections = [];
        foreach (array_keys(S::CONCEPT_SECTIONS) as $key) {
            $sections[$key] = $this->words(60);
        }

        return array_replace_recursive([
            'title' => 'Nurse-led follow-up calls to improve retention in HIV care',
            'mentor_name' => 'Dr. Joseph Kweka', 'mentor_designation' => 'Chief Research Scientist',
            'mentor_centre' => 'Mwanza Centre', 'mentor_suitability' => 'Led three HIV retention studies.',
            'declared_no_prior_large_grant' => '1',
            'sections' => $sections,
            'budget' => [['item' => 'Research assistants', 'amount' => '3,600,000'], ['item' => 'Airtime', 'amount' => '1,200,000']],
        ], $overrides);
    }

    private function proposalForm(array $budget = null): array
    {
        $sections = [];
        foreach (array_keys(S::PROPOSAL_SECTIONS) as $key) {
            $sections[$key] = $this->words(80);
        }

        return ['sections' => $sections, 'budget' => $budget ?? [
            ['category' => 'personnel', 'item' => 'Research assistants', 'q1' => '900000', 'q2' => '900000', 'q3' => '900000', 'q4' => '900000'],
            ['category' => 'equipment', 'item' => 'Phones', 'q1' => '950000', 'q2' => '0', 'q3' => '0', 'q4' => '0'],
            ['category' => 'research', 'item' => 'Travel', 'q1' => '1000000', 'q2' => '800000', 'q3' => '800000', 'q4' => '530000'],
            ['category' => 'indirect', 'item' => 'Overheads', 'q1' => '200000', 'q2' => '200000', 'q3' => '200000', 'q4' => '170000'],
        ]];
    }

    private function applyAndSubmitConcept(): SeedApplication
    {
        $this->actingAs($this->pi)->post(route('seed.apply'))->assertRedirect();
        $application = SeedApplication::firstOrFail();
        $this->actingAs($this->pi)->post(route('seed.applications.submit', $application), $this->conceptForm())
            ->assertRedirect(route('seed.applications.show', $application));

        return $application->fresh();
    }

    /** GMU adds two reviewers, both declare no conflict, and the round opens. */
    private function sendToReviewers(SeedApplication $application): void
    {
        foreach ([$this->reviewerA, $this->reviewerB] as $reviewer) {
            $this->actingAs($this->gmu)->post(route('seed.submissions.assign', $application), ['reviewer_id' => $reviewer->id])->assertSessionHasNoErrors();
        }
        foreach (SeedReviewerAssignment::where('stage', $application->fresh()->stage())->get() as $a) {
            $this->actingAs($a->reviewer)->post(route('seed.reviews.declare', $a), ['conflict' => '0'])->assertSessionHasNoErrors();
        }
        $this->actingAs($this->gmu)->post(route('seed.submissions.start-review', $application), [])->assertSessionHasNoErrors();
    }

    private function review(User $reviewer, SeedApplication $application, string $recommendation, ?array $scores = null): void
    {
        $assignment = SeedReviewerAssignment::where('reviewer_id', $reviewer->id)->where('stage', $application->fresh()->stage())->firstOrFail();
        $this->actingAs($reviewer)->put(route('seed.reviews.save', $assignment), [
            'action' => 'submit', 'recommendation' => $recommendation,
            'comments' => 'The implementation question is clear.', 'confidential_note' => 'Only for GMU.',
            'scores' => $scores ?? [],
        ])->assertSessionHasNoErrors();
    }

    private function goodScores(): array
    {
        return ['relevance' => 17, 'science' => 21.5, 'feasibility' => 17, 'impact' => 13, 'development' => 9, 'innovation' => 7];
    }

    // ────────────────────────────────────────────────────────────────────────

    public function test_the_whole_call_from_concept_note_to_awarded_grant(): void
    {
        // Concept note: incomplete first, then complete.
        $this->actingAs($this->pi)->post(route('seed.apply'))->assertRedirect();
        $application = SeedApplication::firstOrFail();
        $this->assertSame('SG-2627-001', $application->reference);

        $this->actingAs($this->pi)->post(route('seed.applications.submit', $application), $this->conceptForm(['declared_no_prior_large_grant' => '0']))
            ->assertSessionHasErrors('declared_no_prior_large_grant');
        $this->assertSame(S::DRAFT, $application->fresh()->status);

        $application = $this->applyAndSubmitConcept();
        $this->assertSame(S::SUBMITTED, $application->status);
        $this->assertSame(4_800_000, $application->budgetTotal('concept'));

        // GMU screening, then reviewers. Fewer than two reviewers cannot start.
        $this->actingAs($this->gmu)->post(route('seed.submissions.screen', $application), ['result' => 'eligible', 'checks' => ['staff' => '1']])
            ->assertSessionHasNoErrors();
        $this->assertSame(S::SCREENED, $application->fresh()->status);
        $this->actingAs($this->gmu)->post(route('seed.submissions.start-review', $application))->assertSessionHasErrors('reviewers');

        $this->sendToReviewers($application);
        $this->assertSame(S::CONCEPT_REVIEW, $application->fresh()->status);

        // Round 1: split; GMU asks for a revision.
        $this->review($this->reviewerA, $application, 'accept');
        $this->review($this->reviewerB, $application, 'revise');
        $this->actingAs($this->gmu)->post(route('seed.submissions.decide', $application), ['decision' => 'revise'])->assertSessionHasErrors('decision_note');
        $this->actingAs($this->gmu)->post(route('seed.submissions.decide', $application), ['decision' => 'revise', 'decision_note' => 'Name a framework.'])
            ->assertSessionHasNoErrors();
        $this->assertSame(S::CONCEPT_REVISION, $application->fresh()->status);

        // The applicant sees the feedback by letter, never the reviewers' names or the note to GMU.
        $this->actingAs($this->pi)->get(route('seed.applications.show', $application))
            ->assertOk()->assertSee('Reviewer A')->assertSee('Name a framework.')
            ->assertDontSee('Peter Mboya')->assertDontSee('Sara Kimaro')->assertDontSee('Only for GMU.');

        // Response opens round 2 with the same reviewers.
        $this->actingAs($this->pi)->post(route('seed.applications.submit', $application), $this->conceptForm(['response' => 'Added CFIR.']))
            ->assertSessionHasNoErrors();
        $application->refresh();
        $this->assertSame(S::CONCEPT_REVIEW, $application->status);
        $this->assertSame(2, $application->latestRound()->number);

        $this->review($this->reviewerA, $application, 'accept');
        $this->review($this->reviewerB, $application, 'accept');
        $this->actingAs($this->gmu)->post(route('seed.submissions.decide', $application), ['decision' => 'advance'])->assertSessionHasNoErrors();
        $this->assertSame(S::SHORTLISTED, $application->fresh()->status);

        // Full proposal: documents are required.
        $this->actingAs($this->pi)->post(route('seed.applications.submit', $application), $this->proposalForm())
            ->assertSessionHasErrors(['document_mentor_letter', 'document_cv']);
        foreach (['mentor_letter', 'cv'] as $kind) {
            $this->actingAs($this->pi)->post(route('seed.applications.documents.store', $application), [
                'kind' => $kind, 'file' => UploadedFile::fake()->create("{$kind}.pdf", 100, 'application/pdf'),
            ])->assertSessionHasNoErrors();
        }
        $this->actingAs($this->pi)->post(route('seed.applications.submit', $application), $this->proposalForm())->assertSessionHasNoErrors();
        $application->refresh();
        $this->assertSame(S::PROPOSAL_SUBMITTED, $application->status);
        $this->assertSame(8_450_000, $application->budgetTotal('proposal'));

        // Scored review. Below the science floor it cannot be recommended.
        $this->sendToReviewers($application);
        $this->review($this->reviewerA, $application, 'accept', ['science' => 12] + $this->goodScores());
        $this->review($this->reviewerB, $application, 'accept', ['science' => 12] + $this->goodScores());
        $this->actingAs($this->gmu)->post(route('seed.submissions.decide', $application), ['decision' => 'advance'])->assertSessionHasErrors('decision');
        $this->actingAs($this->gmu)->post(route('seed.submissions.decide', $application), ['decision' => 'revise', 'decision_note' => 'Strengthen methods.'])->assertSessionHasNoErrors();
        $this->actingAs($this->pi)->post(route('seed.applications.submit', $application), $this->proposalForm() + ['response' => 'Methods expanded.'])->assertSessionHasNoErrors();
        $this->review($this->reviewerA, $application, 'accept', $this->goodScores());
        $this->review($this->reviewerB, $application, 'accept', ['science' => 20] + $this->goodScores());
        $means = $application->fresh()->load('rounds.reviews')->meanScores();
        $this->assertEqualsWithDelta(83.8, $means['total'], 0.001);   // (84.5 + 83) / 2
        $this->assertEqualsWithDelta(20.8, $means['science'], 0.001);
        $this->actingAs($this->gmu)->post(route('seed.submissions.decide', $application), ['decision' => 'advance'])->assertSessionHasNoErrors();
        $this->assertSame(S::RECOMMENDED, $application->fresh()->status);

        // Approval: GMU (from the ranking), then the DRCP, then the DG with a condition.
        $this->actingAs($this->gmu)->get(route('seed.ranking'))->assertOk()->assertSee($application->title);
        $this->actingAs($this->gmu)->post(route('seed.ranking.send'), ['ids' => [$application->id]])->assertSessionHasNoErrors();
        $this->assertSame(S::WITH_DRCP, $application->fresh()->status);

        $drcp = User::whereHas('role', fn ($q) => $q->where('name', 'drcp'))->first();
        $dg = User::whereHas('role', fn ($q) => $q->where('name', 'dg'))->first();
        $this->actingAs($dg)->post(route('seed.approvals.decide', $application), ['decision' => 'approved'])->assertSessionHasErrors('status');
        $this->actingAs($drcp)->post(route('seed.approvals.decide', $application), ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->actingAs($dg)->post(route('seed.approvals.decide', $application), ['decision' => 'conditions'])->assertSessionHasErrors('note');
        $this->actingAs($dg)->post(route('seed.approvals.decide', $application), ['decision' => 'conditions', 'note' => 'Ethics approval before data collection.'])
            ->assertSessionHasNoErrors();
        $application->refresh();
        $this->assertSame(S::APPROVED, $application->status);
        $this->assertSame(8_450_000, $application->approved_amount);

        // Agreement: issued, accepted online with all three confirmations, signed copy uploaded.
        $this->actingAs($this->gmu)->post(route('seed.submissions.issue-agreement', $application))->assertRedirect();
        $this->actingAs($this->pi)->get(route('seed.applications.agreement', $application))
            ->assertOk()->assertSee('TZS 8,450,000')->assertSee('Ethics approval before data collection.');
        $this->actingAs($this->pi)->post(route('seed.applications.agreement.accept', $application), ['read' => '1'])->assertSessionHasErrors(['ethics', 'reporting']);
        $this->actingAs($this->pi)->post(route('seed.applications.agreement.accept', $application), ['read' => '1', 'ethics' => '1', 'reporting' => '1'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->gmu)->post(route('seed.submissions.signed-agreement', $application), [
            'file' => UploadedFile::fake()->create('signed.pdf', 200, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertSame(S::AWARDED, $application->fresh()->status);

        // Everyone involved was told along the way.
        $this->assertGreaterThan(5, $this->pi->notifications()->count());
    }

    public function test_only_staff_under_five_years_service_can_lead_an_application(): void
    {
        $senior = $this->user('pi', ['employment_type' => 'permanent', 'first_appointment_date' => now()->subYears(6)]);

        $this->actingAs($senior)->post(route('seed.apply'))->assertSessionHasErrors('eligibility');
        $this->assertSame(0, SeedApplication::count());
    }

    public function test_one_application_per_lead_applicant(): void
    {
        $this->actingAs($this->pi)->post(route('seed.apply'));
        $this->actingAs($this->pi)->post(route('seed.apply'));

        $this->assertSame(1, SeedApplication::count());
    }

    public function test_the_concept_budget_must_fall_in_the_award_range(): void
    {
        $this->actingAs($this->pi)->post(route('seed.apply'));
        $application = SeedApplication::first();

        $this->actingAs($this->pi)->post(route('seed.applications.submit', $application),
            $this->conceptForm(['budget' => [['item' => 'Everything', 'amount' => '12000000'], ['item' => '', 'amount' => '']]]))
            ->assertSessionHasErrors('budget');
    }

    public function test_equipment_over_the_cap_blocks_the_full_proposal(): void
    {
        $application = $this->applyAndSubmitConcept();
        $application->forceFill(['status' => S::SHORTLISTED])->save();

        $this->actingAs($this->pi)->post(route('seed.applications.submit', $application), $this->proposalForm([
            ['category' => 'equipment', 'item' => 'Analyser', 'q1' => '3000000', 'q2' => '0', 'q3' => '0', 'q4' => '0'],
            ['category' => 'research', 'item' => 'Fieldwork', 'q1' => '2000000', 'q2' => '1000000', 'q3' => '0', 'q4' => '0'],
        ]))->assertSessionHasErrors('budget_equipment');
    }

    public function test_applications_are_private_to_the_applicant_and_gmu(): void
    {
        $application = $this->applyAndSubmitConcept();
        $other = $this->user('pi');

        $this->actingAs($other)->get(route('seed.applications.show', $application))->assertForbidden();
        $this->actingAs($other)->get(route('seed.submissions.index'))->assertForbidden();
        $this->actingAs($this->pi)->get(route('seed.submissions.show', $application))->assertForbidden();
        $this->actingAs($this->pi)->post(route('seed.submissions.screen', $application), ['result' => 'eligible'])->assertForbidden();
        $this->actingAs($this->gmu)->get(route('seed.submissions.show', $application))->assertOk()->assertSee($application->title);

        // The DRCP and DG only see applications that have reached them.
        $drcp = User::whereHas('role', fn ($q) => $q->where('name', 'drcp'))->first();
        $this->actingAs($drcp)->get(route('seed.applications.show', $application))->assertForbidden();
    }

    public function test_a_reviewer_declares_before_seeing_the_application_and_a_conflict_removes_them(): void
    {
        $application = $this->applyAndSubmitConcept();
        $this->actingAs($this->gmu)->post(route('seed.submissions.screen', $application), ['result' => 'eligible']);
        $this->actingAs($this->gmu)->post(route('seed.submissions.assign', $application), ['reviewer_id' => $this->reviewerA->id]);
        $assignment = SeedReviewerAssignment::firstOrFail();

        $this->actingAs($this->reviewerA)->get(route('seed.reviews.show', $assignment))
            ->assertOk()->assertSee('Declare your interests')->assertDontSee($this->words(60));

        $this->actingAs($this->reviewerB)->get(route('seed.reviews.show', $assignment))->assertForbidden();

        $this->actingAs($this->reviewerA)->post(route('seed.reviews.declare', $assignment), ['conflict' => '1'])->assertSessionHasErrors('coi_note');
        $this->actingAs($this->reviewerA)->post(route('seed.reviews.declare', $assignment), ['conflict' => '1', 'coi_note' => 'Same section.'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($assignment->fresh()->isActive());
        $this->assertSame(1, $this->gmu->notifications()->where('type', 'seed_conflict')->count());
        $this->actingAs($this->gmu)->post(route('seed.submissions.assign', $application), ['reviewer_id' => $this->pi->id])
            ->assertSessionHasErrors('reviewer_id');
    }

    public function test_a_reviewer_can_declare_straight_after_being_assigned(): void
    {
        $application = $this->applyAndSubmitConcept();
        $workflow = app(\App\Services\SeedGrant\Workflow::class);
        $workflow->screen($application, $this->gmu, [], true, null);

        // The assignment object returned by the workflow, not a fresh copy from the database.
        $assignment = $workflow->assignReviewer($application->fresh(), $this->reviewerA, $this->gmu);
        $workflow->declareInterest($assignment, false, null);

        $this->assertTrue($assignment->fresh()->canReview());
    }

    public function test_each_role_lands_on_its_own_dashboard(): void
    {
        $this->call->update(['status' => 'open']);
        $application = $this->applyAndSubmitConcept();

        $this->actingAs($this->gmu)->get(route('dashboard'))->assertOk()->assertSee('Needs your action')->assertSee($application->reference);
        $this->actingAs($this->pi)->get(route('dashboard'))->assertOk()->assertSee('Where your application is');

        $drcp = User::whereHas('role', fn ($q) => $q->where('name', 'drcp'))->first();
        $this->actingAs($drcp)->get(route('dashboard'))->assertRedirect(route('seed.approvals.index'));
        $this->actingAs($drcp)->get(route('seed.approvals.index'))->assertOk();
    }
}
