<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit finding M3 — portfolio totals added different currencies together.
 *
 * Each record keeps its contract amount and currency, and gains the equivalent
 * in the reporting currency plus the rate that produced it, so aggregates are
 * meaningful and historical figures do not move when a rate is updated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency');
            $table->decimal('base_total_budget', 18, 2)->nullable()->after('exchange_rate');
        });

        Schema::table('award_budgets', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency');
            $table->decimal('base_allocated_amount', 18, 2)->nullable()->after('exchange_rate');
            $table->decimal('base_spent_amount', 18, 2)->nullable()->after('base_allocated_amount');
        });

        Schema::table('funding_calls', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency');
            $table->decimal('base_estimated_budget', 18, 2)->nullable()->after('exchange_rate');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency');
            $table->decimal('base_requested_budget', 18, 2)->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('awards', fn (Blueprint $t) => $t->dropColumn(['exchange_rate', 'base_total_budget']));
        Schema::table('award_budgets', fn (Blueprint $t) => $t->dropColumn(['exchange_rate', 'base_allocated_amount', 'base_spent_amount']));
        Schema::table('funding_calls', fn (Blueprint $t) => $t->dropColumn(['exchange_rate', 'base_estimated_budget']));
        Schema::table('proposals', fn (Blueprint $t) => $t->dropColumn(['exchange_rate', 'base_requested_budget']));
    }
};
