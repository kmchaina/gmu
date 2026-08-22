<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GmuNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'message', 'related_id', 'related_type', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }

    public function related() { return $this->morphTo(); }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
