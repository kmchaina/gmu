<?php

namespace App\Models\Seed;

use Illuminate\Database\Eloquent\Model;

/** A budget line. Concept-stage lines hold their amount in q1. */
class SeedBudgetItem extends Model
{
    protected $fillable = ['stage', 'category', 'item', 'q1', 'q2', 'q3', 'q4', 'position'];

    protected function casts(): array
    {
        return ['q1' => 'integer', 'q2' => 'integer', 'q3' => 'integer', 'q4' => 'integer'];
    }

    public function total(): int
    {
        return $this->q1 + $this->q2 + $this->q3 + $this->q4;
    }
}
