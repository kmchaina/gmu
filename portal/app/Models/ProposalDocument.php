<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalDocument extends Model
{
    protected $fillable = [
        'proposal_id', 'uploaded_by', 'document_type', 'title',
        'file_path', 'file_name', 'file_size', 'version',
    ];

    public function proposal() { return $this->belongsTo(Proposal::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
