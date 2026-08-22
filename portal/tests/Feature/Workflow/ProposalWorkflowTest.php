<?php

namespace Tests\Feature\Workflow;

use App\Models\GmuNotification;
use App\Models\ProposalApproval;
use App\Models\ProposalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsGrantFixtures;
use Tests\TestCase;

/**
 * Audit findings M4, M7 and M9 — the parts of the pre-award workflow that were
 * missing: notifications, proposal artefacts, and institutional sign-off.
 */
class ProposalWorkflowTest extends TestCase
{
    use RefreshDatabase, BuildsGrantFixtures;

    public function test_a_returned_review_sends_the_proposal_back_for_work(): void
    {
        $scientist = $this->user('research_scientist');
        $proposal  = $this->proposal($this->user('pi'), null, ['status' => 'internal_review']);

        $this->actingAs($scientist)->post(route('proposals.review', $proposal), [
            'status'   => 'needs_revision',
            'comments' => 'The sample size calculation needs to be revisited before this goes further.',
        ])->assertRedirect();

        // Finding M9: this used to leave the proposal sitting in review forever.
        $this->assertSame('under_development', $proposal->fresh()->status);
    }

    public function test_the_lead_pi_is_notified_when_the_proposal_moves(): void
    {
        $pi          = $this->user('pi');
        $coordinator = $this->user('coordinator');
        $proposal    = $this->proposal($pi);

        $this->actingAs($coordinator)->put(route('proposals.update', $proposal), [
            'funding_call_id' => $proposal->funding_call_id,
            'lead_pi_id'      => $pi->id,
            'title'           => $proposal->title,
            'initiation_type' => 'gmu_initiated',
            'status'          => 'internal_review',
            'currency'        => 'USD',
        ])->assertRedirect();

        // Finding M4: nothing in the system ever created a notification.
        $this->assertTrue(
            GmuNotification::where('user_id', $pi->id)->where('type', 'proposal_status')->exists()
        );
    }

    public function test_reviewers_are_asked_for_the_track_they_own(): void
    {
        $pi        = $this->user('pi');
        $scientist = $this->user('research_scientist');
        $finance   = $this->user('finance_officer');
        $legal     = $this->user('legal_officer');
        $proposal  = $this->proposal($pi);

        $this->actingAs($this->user('coordinator'))->put(route('proposals.update', $proposal), [
            'funding_call_id' => $proposal->funding_call_id,
            'lead_pi_id'      => $pi->id,
            'title'           => $proposal->title,
            'initiation_type' => 'gmu_initiated',
            'status'          => 'internal_review',
            'currency'        => 'USD',
        ]);

        foreach ([$scientist, $finance, $legal] as $reviewer) {
            $this->assertTrue(
                GmuNotification::where('user_id', $reviewer->id)->where('type', 'review_assigned')->exists(),
                'expected a review request for ' . $reviewer->role->name
            );
        }

        // The PI is not asked to review their own proposal.
        $this->assertFalse(
            GmuNotification::where('user_id', $pi->id)->where('type', 'review_assigned')->exists()
        );
    }

    public function test_documents_can_be_attached_to_a_proposal_and_are_versioned(): void
    {
        Storage::fake('local');

        $pi       = $this->user('pi');
        $proposal = $this->proposal($pi);

        foreach (['v1', 'v2'] as $round) {
            $this->actingAs($pi)->post(route('proposals.documents.store', $proposal), [
                'title'         => 'Concept note ' . $round,
                'document_type' => 'concept_note',
                'file'          => UploadedFile::fake()->create('concept.pdf', 20, 'application/pdf'),
            ])->assertRedirect();
        }

        $versions = ProposalDocument::where('proposal_id', $proposal->id)->pluck('version')->all();
        $this->assertSame([1, 2], $versions);

        // A colleague outside the team cannot pull the file.
        $document = ProposalDocument::first();
        $this->actingAs($this->user('pi'))
            ->get(route('proposals.documents.download', [$proposal, $document]))
            ->assertForbidden();
    }

    public function test_team_members_can_be_added_and_are_told(): void
    {
        $pi        = $this->user('pi');
        $colleague = $this->user('research_scientist');
        $proposal  = $this->proposal($pi);

        $this->actingAs($pi)->post(route('proposals.team.store', $proposal), [
            'user_id' => $colleague->id,
            'role'    => 'co_investigator',
        ])->assertRedirect();

        $this->assertTrue($proposal->teamMembers()->where('user_id', $colleague->id)->exists());
        $this->assertTrue(GmuNotification::where('user_id', $colleague->id)->where('type', 'team_added')->exists());

        // Being on the team means being able to read the proposal.
        $this->actingAs($colleague)->get(route('proposals.show', $proposal))->assertOk();
    }

    public function test_institutional_approval_runs_drcp_then_dg(): void
    {
        $pi       = $this->user('pi');
        $drcp     = $this->user('drcp');
        $dg       = $this->user('dg');
        $proposal = $this->proposal($pi, null, ['status' => 'internal_review']);

        // Complete the three internal reviews.
        foreach ([['research_scientist', 'technical'], ['legal_officer', 'compliance'], ['finance_officer', 'budget']] as [$role, $track]) {
            $proposal->reviews()->create([
                'review_type' => $track,
                'status'      => 'approved',
                'reviewer_id' => $this->user($role)->id,
                'reviewed_at' => now(),
            ]);
        }

        // The DG cannot sign before the DRCP.
        $this->actingAs($dg)->post(route('proposals.approval', $proposal), [
            'level' => 'dg', 'status' => 'approved',
        ])->assertRedirect();
        $this->assertFalse($proposal->fresh()->hasApprovalAt('dg'));

        // A PI cannot sign at all.
        $this->actingAs($pi)->post(route('proposals.approval', $proposal), [
            'level' => 'drcp', 'status' => 'approved',
        ])->assertForbidden();

        $this->actingAs($drcp)->post(route('proposals.approval', $proposal), [
            'level' => 'drcp', 'status' => 'approved', 'comments' => 'Aligned with institutional priorities.',
        ])->assertRedirect();

        $this->actingAs($dg)->post(route('proposals.approval', $proposal), [
            'level' => 'dg', 'status' => 'approved', 'letter_of_support_issued' => 1,
        ])->assertRedirect();

        $proposal->refresh()->load('approvals');
        $this->assertTrue($proposal->isClearedForSubmission());
        $this->assertSame('approved', $proposal->status);
        $this->assertTrue(ProposalApproval::where('level', 'dg')->value('letter_of_support_issued'));
        $this->assertTrue(GmuNotification::where('user_id', $pi->id)->where('type', 'proposal_cleared')->exists());
    }

    public function test_a_returned_institutional_decision_sends_the_proposal_back(): void
    {
        $pi       = $this->user('pi');
        $drcp     = $this->user('drcp');
        $proposal = $this->proposal($pi, null, ['status' => 'internal_review']);

        foreach (['technical', 'compliance', 'budget'] as $track) {
            $proposal->reviews()->create([
                'review_type' => $track, 'status' => 'approved',
                'reviewer_id' => $this->user('coordinator')->id, 'reviewed_at' => now(),
            ]);
        }

        $this->actingAs($drcp)->post(route('proposals.approval', $proposal), [
            'level' => 'drcp', 'status' => 'returned', 'comments' => 'Budget exceeds the ceiling in the call.',
        ])->assertRedirect();

        $this->assertSame('under_development', $proposal->fresh()->status);
    }
}
