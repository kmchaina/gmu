<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    // Override in model to customise which fields are logged (empty = all fillable)
    protected function auditableFields(): array
    {
        return [];
    }

    // Override in model to return a human-readable label for the log
    public function auditLabel(): string
    {
        return class_basename($this) . ' #' . ($this->getKey() ?? 'new');
    }

    // Fields that should never appear in the log (passwords, tokens, etc.)
    private array $neverLog = ['password', 'remember_token', 'two_factor_secret'];

    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->writeLog('created', null, $model->getLoggableAttributes());
        });

        static::updated(function ($model) {
            $changed = $model->getChangedLoggable();
            if (empty($changed['new'])) return;
            $model->writeLog('updated', $changed['old'], $changed['new']);
        });

        static::deleted(function ($model) {
            $model->writeLog('deleted', $model->getLoggableAttributes(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->writeLog('restored', null, $model->getLoggableAttributes());
            });
        }
    }

    private function writeLog(string $action, ?array $old, ?array $new): void
    {
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'model_type'  => get_class($this),
            'model_id'    => $this->getKey(),
            'model_label' => $this->auditLabel(),
            'action'      => $action,
            'description' => $this->describeAction($action),
            'old_values'  => $old,
            'new_values'  => $new,
            'ip_address'  => Request::ip(),
            'user_agent'  => substr(Request::userAgent() ?? '', 0, 500),
            'created_at'  => now(),
        ]);
    }

    private function describeAction(string $action): string
    {
        $label = $this->auditLabel();
        return match ($action) {
            'created'  => "Created {$label}",
            'updated'  => "Updated {$label}",
            'deleted'  => "Deleted {$label}",
            'restored' => "Restored {$label}",
            default    => ucfirst($action) . " {$label}",
        };
    }

    private function getLoggableAttributes(): array
    {
        $fields = $this->auditableFields() ?: $this->getFillable();
        return collect($this->getAttributes())
            ->only($fields)
            ->except($this->neverLog)
            ->toArray();
    }

    private function getChangedLoggable(): array
    {
        $fields   = $this->auditableFields() ?: $this->getFillable();
        $dirty    = $this->getDirty();
        $filtered = array_intersect_key($dirty, array_flip($fields));
        $filtered = array_diff_key($filtered, array_flip($this->neverLog));

        if (empty($filtered)) return ['old' => [], 'new' => []];

        $old = [];
        $new = [];
        foreach ($filtered as $field => $newVal) {
            $old[$field] = $this->getOriginal($field);
            $new[$field] = $newVal;
        }
        return compact('old', 'new');
    }

    // Relationship: get all logs for this model instance
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'model');
    }
}
