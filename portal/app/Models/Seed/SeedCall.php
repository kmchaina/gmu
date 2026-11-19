<?php

namespace App\Models\Seed;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SeedCall extends Model
{
    use LogsActivity;

    protected $fillable = [
        'title', 'code', 'fiscal_year', 'summary', 'min_amount', 'max_amount', 'envelope',
        'duration_months', 'equipment_cap_percent', 'concept_deadline', 'proposal_deadline', 'status',
    ];

    public const STATUSES = ['draft' => 'Draft (hidden)', 'open' => 'Open', 'closed' => 'Closed'];

    protected function casts(): array
    {
        return [
            'concept_deadline'  => 'date',
            'proposal_deadline' => 'date',
            'min_amount'        => 'integer',
            'max_amount'        => 'integer',
            'envelope'          => 'integer',
        ];
    }

    public function auditLabel(): string
    {
        return 'Seed grant call ' . $this->fiscal_year;
    }

    public function applications()
    {
        return $this->hasMany(SeedApplication::class);
    }

    /** The call people apply to now: the latest one. */
    public static function current(): ?self
    {
        return static::latest('id')->first();
    }

    public function isVisible(): bool
    {
        return $this->status !== 'draft';
    }

    public function acceptingConceptNotes(): bool
    {
        return $this->status === 'open'
            && (! $this->concept_deadline || $this->concept_deadline->copy()->endOfDay()->isFuture());
    }

    public function acceptingProposals(): bool
    {
        return ! $this->proposal_deadline || $this->proposal_deadline->copy()->endOfDay()->isFuture();
    }
}
