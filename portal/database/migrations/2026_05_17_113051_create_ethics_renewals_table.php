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
        Schema::create('ethics_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained('awards')->cascadeOnDelete();
            $table->string('ethics_number');
            $table->string('issuing_body')->nullable();
            $table->date('approval_date')->nullable();
            $table->date('expiry_date');
            $table->enum('renewal_status', ['valid', 'renewal_due', 'expired', 'renewed'])->default('valid');
            $table->boolean('reminder_sent_60')->default(false);
            $table->boolean('reminder_sent_30')->default(false);
            $table->string('certificate_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ethics_renewals');
    }
};
