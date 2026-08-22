<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes, LogsActivity;

    public function auditLabel(): string
    {
        return 'Proposal: ' . ($this->title ?? '#' . $this->getKey());
    }

    protected $fillable = [
        'funding_call_id', 'lead_pi_id', 'assigned_officer_id', 'title', 'abstract',
        'initiation_type', 'status', 'target_submission_date', 'submitted_at',
        'outcome_date', 'outcome_notes', 'requested_budget', 'currency',
    ];

    protected $casts = [
        'target_submission_date' => 'date',
        'submitted_at' => 'date',
        'outcome_date' => 'date',
        'requested_budget' => 'decimal:2',
    ];

    public function fundingCall() { return $this->belongsTo(FundingCall::class); }
    public function leadPi() { return $this->belongsTo(User::class, 'lead_pi_id'); }
    public function assignedOfficer() { return $this->belongsTo(User::class, 'assigned_officer_id'); }
    public function teamMembers() { return $this->hasMany(ProposalTeamMember::class); }
    public function reviews() { return $this->hasMany(ProposalReview::class); }
    public function documents() { return $this->hasMany(ProposalDocument::class); }
    public function award() { return $this->hasOne(Award::class); }

    public function isReviewComplete(): bool
    {
        return $this->reviews()->where('status', 'approved')->count() === 3;
    }
}
