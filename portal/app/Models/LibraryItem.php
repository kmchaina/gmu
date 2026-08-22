<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryItem extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'created_by', 'updated_by', 'title', 'category', 'content',
        'usage_notes', 'tags', 'funder_relevance', 'is_approved', 'use_count',
    ];

    protected $casts = [
        'tags'        => 'array',
        'is_approved' => 'boolean',
    ];

    public function auditLabel(): string
    {
        return 'Library Item: ' . $this->title;
    }

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }

    public static function categories(): array
    {
        return [
            'capabilities_statement'  => 'Capabilities Statement',
            'facilities_equipment'    => 'Facilities & Equipment',
            'budget_justification'    => 'Budget Justification',
            'data_management'         => 'Data Management Plan',
            'ethical_framework'       => 'Ethical Framework',
            'institutional_background'=> 'Institutional Background',
            'cv_biosketch'            => 'CV / Biosketch',
            'other'                   => 'Other',
        ];
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->whereFullText(['title', 'content'], $term);
    }

    public function incrementUseCount(): void
    {
        $this->increment('use_count');
    }
}
