<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closeout & Lessons Learned — the eighth Level-1 process of the GM-BPF, and
 * the only one with nothing built for it at the time of the audit.
 *
 * Workflow D of the framework: final technical report submitted, final
 * financial statement reconciled, contractual obligations completed, the
 * project archived, lessons learned documented. Each of those is a step here,
 * with the reconciliation figures the donor will ask about and the institutional
 * memory that makes the next proposal better.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_closeouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->unique()->constrained('awards')->cascadeOnDelete();
            $table->string('status', 20)->default('not_started'); // not_started, in_progress, completed

            // Workflow D steps 1-3
            $table->timestamp('final_technical_report_at')->nullable();
            $table->timestamp('final_financial_report_at')->nullable();
            $table->timestamp('financial_reconciled_at')->nullable();
            $table->timestamp('obligations_completed_at')->nullable();
            $table->timestamp('assets_transferred_at')->nullable();
            $table->timestamp('ethics_closed_at')->nullable();
            $table->timestamp('sub_awards_closed_at')->nullable();

            // Reconciliation, in the contract currency and the reporting one
            $table->decimal('total_expenditure', 15, 2)->nullable();
            $table->decimal('unspent_balance', 15, 2)->nullable();
            $table->decimal('funds_returned', 15, 2)->nullable();
            $table->date('funds_returned_on')->nullable();

            // Step 4: archive
            $table->timestamp('archived_at')->nullable();
            $table->string('archive_reference')->nullable();

            // Step 5: lessons learned
            $table->text('what_went_well')->nullable();
            $table->text('what_was_difficult')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('donor_feedback')->nullable();

            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_closeouts');
    }
};
