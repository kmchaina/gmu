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
        Schema::create('fcoi_disclosures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained('proposals')->nullOnDelete();
            $table->year('disclosure_year');

            // Has any financial interest in entities whose products/services relate to the research?
            $table->boolean('has_conflict')->default(false);
            $table->text('conflict_description')->nullable();     // required if has_conflict=true

            // Management plan (required by some funders if conflict exists)
            $table->text('management_plan')->nullable();
            $table->boolean('management_plan_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->enum('status', ['pending', 'submitted', 'reviewed', 'cleared'])->default('pending');
            $table->text('reviewer_notes')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'proposal_id', 'disclosure_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fcoi_disclosures');
    }
};
