<?php

namespace App\Models\Seed;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class SeedApproval extends Model
{
    use LogsActivity;

    public const LEVELS = ['gmu' => 'GMU', 'drcp' => 'DRCP', 'dg' => 'Director General'];

    public const DECISIONS = ['approved' => 'Approved', 'conditions' => 'Approved with conditions', 'returned' => 'Returned'];

    protected $fillable = ['level', 'decision', 'note', 'decided_by'];

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function application()
    {
        return $this->belongsTo(SeedApplication::class, 'seed_application_id');
    }

    public function auditLabel(): string
    {
        return 'Seed grant approval (' . $this->level . ') for ' . ($this->application?->reference ?? '#' . $this->seed_application_id);
    }
}
