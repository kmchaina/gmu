<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit finding M6 — step B6 of the business process framework says the
 * compliance calendar is generated at award setup. It was only ever typed in
 * by hand, one row at a time. The reporting rhythm agreed with the donor is
 * what the schedule is built from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->string('reporting_frequency', 20)->default('quarterly')->after('status');
            $table->boolean('schedule_generated')->default(false)->after('reporting_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->dropColumn(['reporting_frequency', 'schedule_generated']);
        });
    }
};
