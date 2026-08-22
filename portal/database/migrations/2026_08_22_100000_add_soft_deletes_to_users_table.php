<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit finding C5 — accounts must never be hard-deleted.
 *
 * Grant records outlive the staff who created them: a deleted user row used to
 * cascade through proposals, awards, reports and budgets. Users are now
 * deactivated or soft-deleted, and the foreign keys are tightened separately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
