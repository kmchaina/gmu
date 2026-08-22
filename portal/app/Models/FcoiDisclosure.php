<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class FcoiDisclosure extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id', 'proposal_id', 'disclosure_year',
        'has_conflict', 'conflict_description', 'management_plan',
        'management_plan_approved', 'approved_by', 'approved_at',
        'status', 'reviewer_notes',
    ];

    protected $casts = [
        'has_conflict'              => 'boolean',
        'management_plan_approved'  => 'boolean',
        'approved_at'               => 'datetime',
    ];

    public function auditLabel(): string
    {
        return 'FCOI Disclosure #' . $this->id . ' (' . $this->disclosure_year . ')';
    }

    public function user()     { return $this->belongsTo(User::class); }
    public function proposal() { return $this->belongsTo(Proposal::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
}
