<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amendments and no-cost extensions — the gap that kept Award Negotiation &
 * Setup at "partial" in the audit's coverage matrix.
 *
 * Grants change: the end date moves, the budget is revised, the scope is
 * varied, a partner is added. Each of those is a contractual event with a
 * donor approval behind it, and the award record has to show its own history
 * rather than just its current state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained('awards')->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 30);              // no_cost_extension, budget_revision, scope_change, partner_change, other
            $table->string('reference')->nullable(); // the donor's amendment number
            $table->text('reason');

            // What changed — nulls mean "unchanged by this amendment"
            $table->date('previous_end_date')->nullable();
            $table->date('new_end_date')->nullable();
            $table->decimal('previous_budget', 15, 2)->nullable();
            $table->decimal('new_budget', 15, 2)->nullable();

            $table->date('requested_on')->nullable();
            $table->date('donor_approved_on')->nullable();
            $table->string('status', 20)->default('draft'); // draft, submitted, approved, rejected
            $table->string('approval_file')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['award_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_amendments');
    }
};
