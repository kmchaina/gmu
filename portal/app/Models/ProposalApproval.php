<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * An institutional sign-off on a proposal: DRCP, then Director General
 * (audit finding M9).
 */
class ProposalApproval extends Model
{
    use LogsActivity;

    /** The approval chain, in order. */
    public const LEVELS = [
        'drcp' => 'Directorate of Research Coordination & Promotion',
        'dg'   => 'Director General',
    ];

    protected $fillable = [
        'proposal_id', 'level', 'status', 'decided_by', 'decided_at',
        'comments', 'letter_of_support_issued',
    ];

    protected $casts = [
        'decided_at'               => 'datetime',
        'letter_of_support_issued' => 'boolean',
    ];

    public function auditLabel(): string
    {
        return strtoupper($this->level) . ' approval for proposal #' . $this->proposal_id;
    }

    public function proposal() { return $this->belongsTo(Proposal::class); }
    public function decidedBy() { return $this->belongsTo(User::class, 'decided_by'); }

    public function levelName(): string
    {
        return self::LEVELS[$this->level] ?? $this->level;
    }
}
