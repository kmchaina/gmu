<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonorPolicy extends Model
{
    protected $fillable = [
        'donor_id', 'uploaded_by', 'title', 'policy_type', 'file_path',
        'file_name', 'version', 'effective_date', 'notes',
    ];

    protected $casts = ['effective_date' => 'date'];

    public function donor() { return $this->belongsTo(Donor::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
