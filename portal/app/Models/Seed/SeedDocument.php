<?php

namespace App\Models\Seed;

use App\Support\SeedGrant;
use Illuminate\Database\Eloquent\Model;

class SeedDocument extends Model
{
    protected $fillable = ['kind', 'file_path', 'file_name', 'file_size', 'uploaded_by'];

    public function application()
    {
        return $this->belongsTo(SeedApplication::class, 'seed_application_id');
    }

    public function kindLabel(): string
    {
        return SeedGrant::DOCUMENT_KINDS[$this->kind][0] ?? $this->kind;
    }
}
