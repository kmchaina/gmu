<?php

namespace Tests\Feature\Authorization;

use App\Models\Proposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Audit finding C4 — before Phase 1 any authenticated user could read, rewrite
 * or delete any proposal in the institution.
 */
class ProposalAccessTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    public function test_a_researcher_cannot_open_someone_elses_proposal(): void
    {
        $owner    = $this->user('pi');
        $outsider = $this->user('pi');
        $proposal = $this->proposal($owner);

        $this->actingAs($outsider)->get(route('proposals.show', $proposal))->assertForbidden();
    }

    public function test_a_researcher_cannot_rewrite_someone_elses_proposal(): void
    {
        $owner    = $this->user('pi');
        $outsider = $this->user('pi');
        $proposal = $this->proposal($owner);

        $this->actingAs($outsider)
            ->put(route('proposals.update', $proposal), [
                'funding_call_id' => $proposal->funding_call_id,
                'lead_pi_id'      => $outsider->id,
                'title'           => 'HIJACKED',
                'initiation_type' => 'gmu_initiated',
                'status'          => 'awarded',
                'currency'        => 'USD',
            ])
            ->assertForbidden();

        $proposal->refresh();
        $this->assertSame('Vector surveillance in Kigoma', $proposal->title);
        $this->assertSame($owner->id, $proposal->lead_pi_id);
        $this->assertSame('concept', $proposal->status);
    }

    public function test_a_researcher_cannot_delete_a_proposal(): void
    {
        $owner    = $this->user('pi');
        $proposal = $this->proposal($owner);

        $this->actingAs($owner)->delete(route('proposals.destroy', $proposal))->assertForbidden();
        $this->assertNotNull(Proposal::find($proposal->id));
    }

    public function test_the_lead_pi_may_edit_their_own_draft_but_not_after_review_starts(): void
    {
        $owner    = $this->user('pi');
        $proposal = $this->proposal($owner);

        $payload = [
            'funding_call_id' => $proposal->funding_call_id,
            'lead_pi_id'      => $owner->id,
            'title'           => 'Revised title',
            'initiation_type' => 'gmu_initiated',
            'status'          => 'under_development',
            'currency'        => 'USD',
        ];

        $this->actingAs($owner)->put(route('proposals.update', $proposal), $payload)->assertRedirect();
        $this->assertSame('Revised title', $proposal->fresh()->title);

        $proposal->update(['status' => 'internal_review']);

        $this->actingAs($owner)
            ->put(route('proposals.update', $proposal), $payload + ['title' => 'Late change'])
            ->assertForbidden();
    }

    public function test_the_pipeline_listing_shows_a_researcher_only_their_own_work(): void
    {
        $owner    = $this->user('pi');
        $outsider = $this->user('pi');
        $this->proposal($owner, null, ['title' => 'Owned proposal']);
        $this->proposal($outsider, null, ['title' => 'Someone elses proposal']);

        $this->actingAs($owner)
            ->get(route('proposals.index'))
            ->assertOk()
            ->assertSee('Owned proposal')
            ->assertDontSee('Someone elses proposal');
    }

    public function test_gmu_officers_see_the_whole_pipeline(): void
    {
        $coordinator = $this->user('coordinator');
        $this->proposal($this->user('pi'), null, ['title' => 'Owned proposal']);
        $this->proposal($this->user('pi'), null, ['title' => 'Another teams proposal']);

        $this->actingAs($coordinator)
            ->get(route('proposals.index'))
            ->assertOk()
            ->assertSee('Owned proposal')
            ->assertSee('Another teams proposal');
    }

    public function test_an_account_without_a_role_reaches_nothing(): void
    {
        $nobody   = $this->roleless();
        $proposal = $this->proposal($this->user('pi'));

        $this->actingAs($nobody)->get(route('proposals.index'))->assertForbidden();
        $this->actingAs($nobody)->get(route('proposals.show', $proposal))->assertForbidden();
        $this->actingAs($nobody)->get(route('awards.index'))->assertForbidden();
        $this->actingAs($nobody)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($nobody)->get(route('documents.index'))->assertForbidden();
        $this->actingAs($nobody)->get(route('library.index'))->assertForbidden();
    }
}
