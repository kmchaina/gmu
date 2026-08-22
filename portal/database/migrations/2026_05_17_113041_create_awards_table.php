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
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignId('donor_id')->constrained('donors')->cascadeOnDelete();
            $table->string('contract_number')->unique();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_budget', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->enum('status', ['active', 'completed', 'suspended', 'terminated'])->default('active');
            $table->string('signed_contract')->nullable();
            $table->date('kickoff_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awards');
    }
};
