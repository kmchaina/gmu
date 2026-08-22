<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_call_id')->constrained('funding_calls')->cascadeOnDelete();
            $table->foreignId('lead_pi_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->enum('initiation_type', ['gmu_initiated', 'researcher_initiated'])->default('gmu_initiated');
            $table->enum('status', [
                'concept',
                'under_development',
                'internal_review',
                'approved',
                'submitted',
                'awarded',
                'declined',
                'withdrawn'
            ])->default('concept');
            $table->date('target_submission_date')->nullable();
            $table->date('submitted_at')->nullable();
            $table->date('outcome_date')->nullable();
            $table->text('outcome_notes')->nullable();
            $table->decimal('requested_budget', 15, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
