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
        Schema::create('monitoring_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained('awards')->cascadeOnDelete();
            $table->foreignId('conducted_by')->constrained('users')->cascadeOnDelete();
            $table->date('visit_date');
            $table->string('location')->nullable();
            $table->text('findings');
            $table->text('recommendations')->nullable();
            $table->string('report_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_visits');
    }
};
