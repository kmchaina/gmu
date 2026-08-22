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
        'related_type', 'is_public', 'retention_years', 'retain_until', 'retention_basis',
    ];

    protected $casts = [
        'is_public'    => 'boolean',
        'retain_until' => 'date',
    ];

    /**
     * Default retention by category. Donor contracts and audit evidence carry
     * the longest obligations; templates and SOPs are superseded rather than
     * retained.
     */
    public const RETENTION_YEARS = [
        'contract' => 10,
        'audit'    => 7,
        'report'   => 7,
        'policy'   => 5,
        'sop'      => 5,
        'template' => 3,
        'other'    => 5,
    ];

    /** Past its retention date and due for review. */
    public function isDueForReview(): bool
    {
        return $this->retain_until !== null && $this->retain_until->isPast();
    }

    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function related() { return $this->morphTo(); }
}
