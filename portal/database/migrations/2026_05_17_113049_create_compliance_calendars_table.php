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
        Schema::create('compliance_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained('awards')->cascadeOnDelete();
            $table->string('deliverable_type'); // technical_report, financial_report, ethics_renewal, audit, site_visit, other
            $table->string('title');
            $table->date('due_date');
            $table->enum('status', ['upcoming', 'submitted', 'overdue', 'waived'])->default('upcoming');
            $table->boolean('reminder_sent_30')->default(false);
            $table->boolean('reminder_sent_14')->default(false);
            $table->boolean('reminder_sent_7')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_calendars');
    }
};
