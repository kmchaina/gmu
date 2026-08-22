<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The expenditure ledger behind each budget line.
 *
 * Budget "spent" figures were typed in by hand, with nothing behind them: no
 * transactions, no burn-rate history, and nothing an auditor could trace. Each
 * entry here is one recorded transaction, and the budget line's spent amount
 * is derived from them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenditures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained('awards')->cascadeOnDelete();
            $table->foreignId('award_budget_id')->nullable()->constrained('award_budgets')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('transaction_date');
            $table->string('description');
            $table->string('reference')->nullable();       // voucher, invoice or payment reference
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->decimal('base_amount', 18, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['award_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenditures');
    }
};
