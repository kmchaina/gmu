<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallInterest extends Model
{
    protected $fillable = [
        'funding_call_id', 'user_id', 'motivation', 'relevant_experience',
        'status', 'gmu_notes', 'decided_at', 'decided_by',
    ];

    protected $casts = ['decided_at' => 'datetime'];

    public function fundingCall() { return $this->belongsTo(FundingCall::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function decidedBy() { return $this->belongsTo(User::class, 'decided_by'); }
}
