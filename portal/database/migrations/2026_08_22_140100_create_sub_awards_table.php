<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sub-awards to partner institutions.
 *
 * "Limited monitoring of sub-awards" was one of the weaknesses the
 * benchmarking exercise found across the sector, and the management deck lists
 * sub-award status on the project dashboard. Nothing in the system tracked
 * them: the pass-through money and the institutional risk that comes with it
 * were invisible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained('awards')->cascadeOnDelete();

            $table->string('partner_name');
            $table->string('partner_type', 30)->default('institution'); // institution, university, ngo, cbo, private, government
            $table->string('country')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_email')->nullable();

            $table->string('agreement_number')->nullable();
            $table->string('agreement_file')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->decimal('base_amount', 18, 2)->nullable();
            $table->decimal('disbursed_amount', 15, 2)->default(0);

            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft'); // draft, active, completed, suspended, terminated
            $table->string('reporting_frequency', 20)->default('quarterly');
            $table->date('next_report_due')->nullable();

            // The compliance the pass-through obliges NIMR to carry out
            $table->date('due_diligence_completed_on')->nullable();
            $table->date('last_monitored_on')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['award_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_awards');
    }
};
