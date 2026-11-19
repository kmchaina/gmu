<?php

namespace App\Models\Seed;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * A reviewer on one application at one stage. The reviewer declares any
 * conflict of interest before the application is shown to them; the applicant
 * only ever sees the label (Reviewer A, B...).
 */
class SeedReviewerAssignment extends Model
{
    use LogsActivity;

    protected $fillable = ['stage', 'reviewer_id', 'label', 'coi_status', 'coi_note', 'coi_declared_at', 'assigned_by', 'removed_at'];

    protected function casts(): array
    {
        return ['coi_declared_at' => 'datetime', 'removed_at' => 'datetime'];
    }

    public function application()
    {
        return $this->belongsTo(SeedApplication::class, 'seed_application_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviews()
    {
        return $this->hasMany(SeedReview::class);
    }

    /** Still on the panel: not removed and no conflict declared. */
    public function scopeActive($query)
    {
        return $query->whereNull('removed_at')->where('coi_status', '!=', 'conflict');
    }

    public function isActive(): bool
    {
        return $this->removed_at === null && $this->coi_status !== 'conflict';
    }

    public function canReview(): bool
    {
        return $this->removed_at === null && $this->coi_status === 'none';
    }

    public function auditLabel(): string
    {
        return 'Reviewer ' . $this->label . ' (' . $this->stage . ') on ' . ($this->application?->reference ?? '#' . $this->seed_application_id);
    }
}
