<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit finding M9 — the approval routing shown in the GMU concept.
 *
 * Slide 5 of the management presentation puts DRCP and Director General
 * approval between internal review and submission to the donor, alongside the
 * institutional Letter of Support. Neither had any representation in the
 * system: a proposal went straight from three internal reviews to "approved".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->string('level', 20);                     // drcp, dg
            $table->string('status', 20)->default('pending'); // pending, approved, returned
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('letter_of_support_issued')->default(false);
            $table->timestamps();

            $table->unique(['proposal_id', 'level']);
        });

        // Reviews start unassigned: the officer who holds the track picks it up,
        // rather than the record naming whoever happened to create the proposal.
        Schema::table('proposal_reviews', function (Blueprint $table) {
            $table->foreignId('reviewer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_approvals');
    }
};
