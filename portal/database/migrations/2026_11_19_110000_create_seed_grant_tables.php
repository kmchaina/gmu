<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Early-Career Researchers' Implementation Science Seed Grant.
 *
 * One call per year; applications move concept note -> GMU screening ->
 * review rounds -> shortlist -> full proposal -> scored review rounds ->
 * GMU, DRCP and DG approval -> grant agreement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seed_calls', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code', 12)->unique();          // e.g. 2627, used in references SG-2627-001
            $table->string('fiscal_year', 12);              // 2026/27
            $table->text('summary')->nullable();
            $table->unsignedBigInteger('min_amount');       // TZS
            $table->unsignedBigInteger('max_amount');       // TZS
            $table->unsignedBigInteger('envelope')->nullable(); // total funds available, TZS
            $table->unsignedTinyInteger('duration_months')->default(12);
            $table->unsignedTinyInteger('equipment_cap_percent')->default(20);
            $table->date('concept_deadline')->nullable();
            $table->date('proposal_deadline')->nullable();
            $table->string('status', 12)->default('draft');  // draft, open, closed
            $table->timestamps();
        });

        Schema::create('seed_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_call_id')->constrained('seed_calls')->restrictOnDelete();
            $table->foreignId('pi_id')->constrained('users')->restrictOnDelete();
            $table->string('reference', 20)->unique();
            $table->string('title')->nullable();
            $table->string('status', 24)->default('draft');

            // Mentor, named at concept stage
            $table->string('mentor_name')->nullable();
            $table->string('mentor_designation')->nullable();
            $table->string('mentor_centre')->nullable();
            $table->text('mentor_suitability')->nullable();

            // Section text, keyed by the section lists in App\Support\SeedGrant
            $table->json('concept')->nullable();
            $table->json('proposal')->nullable();

            $table->boolean('declared_no_prior_large_grant')->default(false);
            $table->timestamp('concept_submitted_at')->nullable();
            $table->timestamp('proposal_submitted_at')->nullable();

            // GMU administrative screening of the concept note
            $table->json('screening_checks')->nullable();
            $table->text('screening_note')->nullable();
            $table->foreignId('screened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('screened_at')->nullable();

            // Grant agreement
            $table->unsignedBigInteger('approved_amount')->nullable();
            $table->text('approval_conditions')->nullable();
            $table->timestamp('agreement_issued_at')->nullable();
            $table->timestamp('agreement_accepted_at')->nullable();
            $table->string('signed_agreement_path')->nullable();
            $table->string('signed_agreement_name')->nullable();
            $table->timestamp('signed_agreement_uploaded_at')->nullable();

            $table->timestamps();
            $table->unique(['seed_call_id', 'pi_id']);       // one application as lead PI per call
            $table->index('status');
        });

        Schema::create('seed_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_application_id')->constrained('seed_applications')->cascadeOnDelete();
            $table->string('stage', 10);                     // concept, proposal
            $table->string('category', 12)->nullable();     // proposal only: personnel, equipment, research, indirect
            $table->string('item');
            $table->unsignedBigInteger('q1')->default(0);    // concept items use q1 for the amount
            $table->unsignedBigInteger('q2')->default(0);
            $table->unsignedBigInteger('q3')->default(0);
            $table->unsignedBigInteger('q4')->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('seed_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_application_id')->constrained('seed_applications')->cascadeOnDelete();
            $table->string('kind', 20);                      // mentor_letter, cv, support_letter
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('seed_reviewer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_application_id')->constrained('seed_applications')->cascadeOnDelete();
            $table->string('stage', 10);
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('label', 2);                      // A, B, C: what the applicant sees
            $table->string('coi_status', 10)->default('pending'); // pending, none, conflict
            $table->text('coi_note')->nullable();
            $table->timestamp('coi_declared_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
            $table->unique(['seed_application_id', 'stage', 'reviewer_id'], 'seed_assignment_unique');
        });

        Schema::create('seed_review_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_application_id')->constrained('seed_applications')->cascadeOnDelete();
            $table->string('stage', 10);
            $table->unsignedTinyInteger('number');
            $table->date('due_at')->nullable();
            $table->string('decision', 10)->nullable();      // revise, advance, decline
            $table->text('decision_note')->nullable();
            $table->date('response_due_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('response')->nullable();            // applicant's reply to a "revise" decision
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['seed_application_id', 'stage', 'number']);
        });

        Schema::create('seed_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_review_round_id')->constrained('seed_review_rounds')->cascadeOnDelete();
            $table->foreignId('seed_reviewer_assignment_id')->constrained('seed_reviewer_assignments')->cascadeOnDelete();
            $table->string('recommendation', 10)->nullable(); // accept, revise, reject
            $table->text('comments')->nullable();            // shared with the applicant, unsigned
            $table->text('confidential_note')->nullable();   // GMU only
            $table->json('scores')->nullable();              // full proposal only, keyed by criterion
            $table->decimal('total', 5, 1)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['seed_review_round_id', 'seed_reviewer_assignment_id'], 'seed_review_unique');
        });

        Schema::create('seed_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seed_application_id')->constrained('seed_applications')->cascadeOnDelete();
            $table->string('level', 6);                      // gmu, drcp, dg
            $table->string('decision', 12);                  // approved, conditions, returned
            $table->text('note')->nullable();
            $table->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seed_approvals');
        Schema::dropIfExists('seed_reviews');
        Schema::dropIfExists('seed_review_rounds');
        Schema::dropIfExists('seed_reviewer_assignments');
        Schema::dropIfExists('seed_documents');
        Schema::dropIfExists('seed_budget_items');
        Schema::dropIfExists('seed_applications');
        Schema::dropIfExists('seed_calls');
    }
};
