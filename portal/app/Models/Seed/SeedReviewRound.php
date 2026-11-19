<?php

namespace App\Models\Seed;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SeedReviewRound extends Model
{
    use LogsActivity;

    public const DECISIONS = ['revise' => 'Revise', 'advance' => 'Advance', 'decline' => 'Decline'];

    protected $fillable = ['stage', 'number', 'due_at', 'decision', 'decision_note', 'response_due_at',
        'decided_by', 'decided_at', 'response', 'responded_at'];

    protected function casts(): array
    {
        return ['due_at' => 'date', 'response_due_at' => 'date', 'decided_at' => 'datetime', 'responded_at' => 'datetime'];
    }

    public function application()
    {
        return $this->belongsTo(SeedApplication::class, 'seed_application_id');
    }

    public function reviews()
    {
        return $this->hasMany(SeedReview::class);
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isOpen(): bool
    {
        return $this->decision === null;
    }

    public function auditLabel(): string
    {
        return ucfirst($this->stage) . ' review round ' . $this->number . ' on ' . ($this->application?->reference ?? '#' . $this->seed_application_id);
    }
}
