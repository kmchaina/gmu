<?php

namespace App\Models;

use App\Models\Concerns\ConvertsToBaseCurrency;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * One recorded transaction against an award budget line.
 */
class Expenditure extends Model
{
    use LogsActivity, ConvertsToBaseCurrency;

    protected $fillable = [
        'award_id', 'award_budget_id', 'recorded_by', 'transaction_date', 'description',
        'reference', 'amount', 'currency', 'exchange_rate', 'base_amount', 'notes',
    ];

    protected array $baseCurrencyMap = ['amount' => 'base_amount'];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
        'base_amount'      => 'decimal:2',
    ];

    public function auditLabel(): string
    {
        return 'Expenditure: ' . $this->description . ' (' . $this->currency . ' ' . $this->amount . ')';
    }

    public function award() { return $this->belongsTo(Award::class); }
    public function budgetLine() { return $this->belongsTo(AwardBudget::class, 'award_budget_id'); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }
}
