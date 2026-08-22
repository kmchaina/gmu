<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * A contractual change to an award: an extension, a revised budget, a change
 * of scope or of partners.
 */
class AwardAmendment extends Model
{
    use LogsActivity;

    public const TYPES = [
        'no_cost_extension' => 'No-cost extension',
        'budget_revision'   => 'Budget revision',
        'scope_change'      => 'Change of scope',
        'partner_change'    => 'Change of partners',
        'other'             => 'Other amendment',
    ];

    protected $fillable = [
        'award_id', 'recorded_by', 'type', 'reference', 'reason',
        'previous_end_date', 'new_end_date', 'previous_budget', 'new_budget',
        'requested_on', 'donor_approved_on', 'status', 'approval_file', 'notes',
    ];

    protected $casts = [
        'previous_end_date' => 'date',
        'new_end_date'      => 'date',
        'requested_on'      => 'date',
        'donor_approved_on' => 'date',
        'previous_budget'   => 'decimal:2',
        'new_budget'        => 'decimal:2',
    ];

    public function auditLabel(): string
    {
        return (self::TYPES[$this->type] ?? 'Amendment') . ' on award #' . $this->award_id;
    }

    public function award() { return $this->belongsTo(Award::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }

    public function typeName(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Days the award period moved by, positive for an extension. */
    public function daysExtended(): ?int
    {
        if (! $this->previous_end_date || ! $this->new_end_date) {
            return null;
        }

        return (int) $this->previous_end_date->startOfDay()->diffInDays($this->new_end_date->startOfDay(), false);
    }

    /** Change in the award value, positive for an increase. */
    public function budgetDelta(): ?float
    {
        if ($this->previous_budget === null || $this->new_budget === null) {
            return null;
        }

        return round((float) $this->new_budget - (float) $this->previous_budget, 2);
    }
}
