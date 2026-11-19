<?php

namespace App\Models\Seed;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SeedReview extends Model
{
    use LogsActivity;

    public const RECOMMENDATIONS = ['accept' => 'Accept', 'revise' => 'Revise', 'reject' => 'Reject'];

    protected $fillable = ['seed_reviewer_assignment_id', 'recommendation', 'comments', 'confidential_note', 'scores', 'total', 'submitted_at'];

    protected function casts(): array
    {
        return ['scores' => 'array', 'total' => 'float', 'submitted_at' => 'datetime'];
    }

    public function round()
    {
        return $this->belongsTo(SeedReviewRound::class, 'seed_review_round_id');
    }

    public function assignment()
    {
        return $this->belongsTo(SeedReviewerAssignment::class, 'seed_reviewer_assignment_id');
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function score(string $criterion): ?float
    {
        $value = $this->scores[$criterion] ?? null;

        return $value === null ? null : (float) $value;
    }

    /** What the confidential note must never reach: the applicant. */
    protected function auditableFields(): array
    {
        return ['recommendation', 'total', 'submitted_at'];
    }

    public function auditLabel(): string
    {
        return 'Review by reviewer ' . ($this->assignment?->label ?? '?') . ' in round #' . $this->seed_review_round_id;
    }
}
