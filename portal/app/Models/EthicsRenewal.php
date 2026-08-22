<?php

namespace App\Models;

use App\Support\Days;
use Illuminate\Database\Eloquent\Model;

class EthicsRenewal extends Model
{
    protected $fillable = [
        'award_id', 'ethics_number', 'issuing_body', 'approval_date',
        'expiry_date', 'renewal_status', 'reminder_sent_60',
        'reminder_sent_30', 'certificate_file',
    ];

    protected $casts = [
        'approval_date'    => 'date',
        'expiry_date'      => 'date',
        'reminder_sent_60' => 'boolean',
        'reminder_sent_30' => 'boolean',
    ];

    public function award() { return $this->belongsTo(Award::class); }

    /** Inside the 60-day renewal window, or already expired. */
    public function isExpiringSoon(): bool
    {
        return Days::until($this->expiry_date) <= 60;
    }

    public function daysUntilExpiry(): int
    {
        return Days::until($this->expiry_date);
    }
}
