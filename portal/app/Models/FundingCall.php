<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class FundingCall extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes, LogsActivity;

    public function auditLabel(): string
    {
        return 'Call: ' . ($this->title ?? '#' . $this->getKey());
    }

    protected $fillable = [
        'title', 'donor_id', 'posted_by', 'description', 'theme', 'eligibility',
        'solicitation_number', 'estimated_budget', 'currency', 'open_date', 'deadline',
        'initiation_type', 'status', 'call_document',
    ];

    protected $casts = [
        'deadline' => 'date',
        'open_date' => 'date',
        'estimated_budget' => 'decimal:2',
    ];

    public function donor() { return $this->belongsTo(Donor::class); }
    public function postedBy() { return $this->belongsTo(User::class, 'posted_by'); }
    public function interests() { return $this->hasMany(CallInterest::class); }
    public function proposals() { return $this->hasMany(Proposal::class); }

    public function getDaysUntilDeadlineAttribute(): int
    {
        return now()->diffInDays($this->deadline, false);
    }

    public function isClosingSoon(): bool
    {
        return $this->days_until_deadline <= 30 && $this->days_until_deadline >= 0;
    }
}
