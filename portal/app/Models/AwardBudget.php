<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwardBudget extends Model
{
    protected $fillable = ['award_id', 'category', 'allocated_amount', 'spent_amount', 'currency', 'notes'];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'spent_amount'     => 'decimal:2',
    ];

    public function award() { return $this->belongsTo(Award::class); }
}
