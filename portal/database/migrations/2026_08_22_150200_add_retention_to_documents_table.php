<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document retention — the last gap in Document & Knowledge Management.
 *
 * Donor contracts and audit evidence carry retention obligations, usually
 * five to seven years past closeout. Without a review date, a repository grows
 * until nobody knows what may be disposed of and what must be kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedSmallInteger('retention_years')->nullable()->after('is_public');
            $table->date('retain_until')->nullable()->after('retention_years');
            $table->string('retention_basis')->nullable()->after('retain_until');
            $table->index('retain_until');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['retain_until']);
            $table->dropColumn(['retention_years', 'retain_until', 'retention_basis']);
        });
    }
};
