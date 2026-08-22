<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplianceCalendar extends Model
{
    protected $fillable = [
        'award_id', 'deliverable_type', 'title', 'due_date', 'status',
        'reminder_sent_30', 'reminder_sent_14', 'reminder_sent_7',
        'submitted_at', 'submitted_by', 'notes',
    ];

    protected $casts = [
        'due_date'         => 'date',
        'submitted_at'     => 'datetime',
        'reminder_sent_30' => 'boolean',
        'reminder_sent_14' => 'boolean',
        'reminder_sent_7'  => 'boolean',
    ];

    public function award() { return $this->belongsTo(Award::class); }
    public function submittedBy() { return $this->belongsTo(User::class, 'submitted_by'); }
}
