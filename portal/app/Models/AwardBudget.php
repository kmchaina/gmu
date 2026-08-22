<?php

namespace App\Models;

use App\Models\Concerns\ConvertsToBaseCurrency;
use Illuminate\Database\Eloquent\Model;

class AwardBudget extends Model
{
    use ConvertsToBaseCurrency;

    protected $fillable = [
        'award_id', 'category', 'allocated_amount', 'spent_amount', 'currency', 'notes',
        'exchange_rate', 'base_allocated_amount', 'base_spent_amount',
    ];

    /** Budget figures => reporting-currency equivalents (finding M3). */
    protected array $baseCurrencyMap = [
        'allocated_amount' => 'base_allocated_amount',
        'spent_amount'     => 'base_spent_amount',
    ];

    protected $casts = [
        'allocated_amount'      => 'decimal:2',
        'spent_amount'          => 'decimal:2',
        'base_allocated_amount' => 'decimal:2',
        'base_spent_amount'     => 'decimal:2',
    ];

    public function award() { return $this->belongsTo(Award::class); }

    /** Share of this line that has been spent, 0–100+. */
    public function utilisation(): float
    {
        return $this->allocated_amount > 0
            ? round(($this->spent_amount / $this->allocated_amount) * 100, 1)
            : 0.0;
    }
}
