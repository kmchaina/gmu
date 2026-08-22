<?php

namespace App\Models;

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

    public function isExpiringSoon(): bool
    {
        return $this->expiry_date->diffInDays(now(), false) >= -60;
    }
}
