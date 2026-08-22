<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Models\Concerns\ConvertsToBaseCurrency;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes, LogsActivity, ConvertsToBaseCurrency;

    public function auditLabel(): string
    {
        return 'Proposal: ' . ($this->title ?? '#' . $this->getKey());
    }

    protected $fillable = [
        'funding_call_id', 'lead_pi_id', 'assigned_officer_id', 'title', 'abstract',
        'initiation_type', 'status', 'target_submission_date', 'submitted_at',
        'outcome_date', 'outcome_notes', 'requested_budget', 'currency',
        'exchange_rate', 'base_requested_budget',
    ];

    /** Requested amount => reporting-currency equivalent (finding M3). */
    protected array $baseCurrencyMap = ['requested_budget' => 'base_requested_budget'];

    protected $casts = [
        'target_submission_date' => 'date',
        'submitted_at' => 'date',
        'outcome_date' => 'date',
        'requested_budget' => 'decimal:2',
        'base_requested_budget' => 'decimal:2',
    ];

    public function fundingCall() { return $this->belongsTo(FundingCall::class); }
    public function leadPi() { return $this->belongsTo(User::class, 'lead_pi_id'); }
    public function assignedOfficer() { return $this->belongsTo(User::class, 'assigned_officer_id'); }
    public function teamMembers() { return $this->hasMany(ProposalTeamMember::class); }
    public function reviews() { return $this->hasMany(ProposalReview::class); }
    public function documents() { return $this->hasMany(ProposalDocument::class); }
    public function award() { return $this->hasOne(Award::class); }
    public function approvals() { return $this->hasMany(ProposalApproval::class); }

    /** Has this level signed off? (finding M9) */
    public function hasApprovalAt(string $level): bool
    {
        return $this->approvals
            ->where('level', $level)
            ->where('status', 'approved')
            ->isNotEmpty();
    }

    /** Fully cleared institutionally and ready to go to the donor. */
    public function isClearedForSubmission(): bool
    {
        return $this->hasApprovalAt('drcp') && $this->hasApprovalAt('dg');
    }

    /** Which sign-off the proposal is waiting on, if any. */
    public function awaitingApprovalAt(): ?string
    {
        if (! $this->isReviewComplete()) {
            return null;
        }

        if (! $this->hasApprovalAt('drcp')) {
            return 'drcp';
        }

        return $this->hasApprovalAt('dg') ? null : 'dg';
    }

    /**
     * Statuses that mean the donor has ruled on the proposal.
     *
     * Finding M1: the success rate was computed from the internal "approved"
     * status, which only means cleared for submission. A funded proposal moves
     * to "awarded", so every success was excluded from both sides of the ratio.
     */
    public const DECIDED_STATUSES = ['awarded', 'declined'];

    /** Proposals the donor has ruled on, one way or the other. */
    public function scopeDecided($query)
    {
        return $query->whereIn('status', self::DECIDED_STATUSES);
    }

    /** Proposals that won funding. */
    public function scopeFunded($query)
    {
        return $query->where('status', 'awarded');
    }

    /** Proposals that have gone out to the donor, whatever happened next. */
    public function scopeSubmitted($query)
    {
        return $query->whereIn('status', ['submitted', 'awarded', 'declined']);
    }

    public function isReviewComplete(): bool
    {
        return $this->reviews()->where('status', 'approved')->count() === 3;
    }
}
