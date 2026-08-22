<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalReview extends Model
{
    protected $fillable = [
        'proposal_id', 'reviewer_id', 'review_type', 'status', 'comments', 'score', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function proposal() { return $this->belongsTo(Proposal::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewer_id'); }
}
