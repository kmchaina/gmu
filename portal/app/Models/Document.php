<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uploaded_by', 'category', 'title', 'description', 'file_path',
        'file_name', 'file_size', 'mime_type', 'version', 'related_id',
        'related_type', 'is_public',
    ];

    protected $casts = ['is_public' => 'boolean'];

    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function related() { return $this->morphTo(); }
}
