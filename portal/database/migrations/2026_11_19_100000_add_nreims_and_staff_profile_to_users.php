<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NREIMS single sign-on and the first-login staff profile.
 *
 * nreims_id is the stable link to the NREIMS account (emails can change).
 * The staff fields feed seed grant eligibility: lead applicants must be NIMR
 * staff, permanent or contract, with under five years' service.
 * department and research_center already exist and hold section and centre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('nreims_id')->nullable()->unique()->after('id');
            $table->string('designation')->nullable()->after('phone');
            $table->string('employment_type', 20)->nullable()->after('research_center');
            $table->date('first_appointment_date')->nullable()->after('employment_type');
            $table->timestamp('profile_completed_at')->nullable()->after('first_appointment_date');
            $table->timestamp('last_login_at')->nullable()->after('profile_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nreims_id']);
            $table->dropColumn(['nreims_id', 'designation', 'employment_type', 'first_appointment_date',
                'profile_completed_at', 'last_login_at']);
        });
    }
};
