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
        Schema::create('funding_calls', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('donor_id')->constrained('donors')->cascadeOnDelete();
            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('theme')->nullable();
            $table->text('eligibility')->nullable();
            $table->string('solicitation_number')->nullable();
            $table->decimal('estimated_budget', 15, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->date('open_date')->nullable();
            $table->date('deadline');
            $table->enum('initiation_type', ['gmu_posted', 'researcher_initiated'])->default('gmu_posted');
            $table->enum('status', ['draft', 'open', 'closed', 'archived'])->default('open');
            $table->string('call_document')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_calls');
    }
};
