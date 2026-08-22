<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a report actually went to the donor, as distinct from when the GMU
 * approved it internally.
 *
 * The audit's Post-Award Monitoring row noted this: the system recorded
 * internal verification and stopped there, so nobody could answer "was it sent,
 * and when?" — the question a donor relationship actually turns on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->date('sent_to_donor_on')->nullable()->after('verified_at');
            $table->foreignId('sent_by')->nullable()->after('sent_to_donor_on')->constrained('users')->nullOnDelete();
            $table->string('donor_acknowledgement')->nullable()->after('sent_by');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['sent_by']);
            $table->dropColumn(['sent_to_donor_on', 'sent_by', 'donor_acknowledgement']);
        });
    }
};
