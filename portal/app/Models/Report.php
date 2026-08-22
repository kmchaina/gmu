<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use LogsActivity;

    public function auditLabel(): string
    {
        return 'Report: ' . ($this->title ?? '#' . $this->getKey());
    }
    protected $fillable = [
        'award_id', 'submitted_by', 'verified_by', 'report_type', 'title',
        'period_start', 'period_end', 'due_date', 'submitted_date',
        'status', 'file_path', 'gmu_comments', 'verified_at',
        'sent_to_donor_on', 'sent_by', 'donor_acknowledgement',
        'reminder_sent_30', 'reminder_sent_14', 'reminder_sent_7',
    ];

    protected $casts = [
        'period_start'     => 'date',
        'period_end'       => 'date',
        'due_date'         => 'date',
        'submitted_date'   => 'date',
        'verified_at'      => 'datetime',
        'sent_to_donor_on' => 'date',
        'reminder_sent_30' => 'boolean',
        'reminder_sent_14' => 'boolean',
        'reminder_sent_7'  => 'boolean',
    ];

    public function award() { return $this->belongsTo(Award::class); }
    public function submittedBy() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function verifiedBy() { return $this->belongsTo(User::class, 'verified_by'); }
    public function sentBy() { return $this->belongsTo(User::class, 'sent_by'); }

    /** Approved internally but not yet forwarded to the donor. */
    public function awaitingDonorSubmission(): bool
    {
        return $this->status === 'approved' && $this->sent_to_donor_on === null;
    }
}
