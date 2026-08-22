<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'model_type', 'model_id', 'model_label',
        'action', 'description', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo('model');
    }

    // Convenience: latest logs across the system
    public static function latest(int $limit = 50)
    {
        return static::with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    // Logs for a specific model instance
    public static function forModel(Model $model, int $limit = 100)
    {
        return static::with('user')
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
