<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes, LogsActivity;

    public function auditLabel(): string
    {
        return 'Award ' . ($this->contract_number ?? $this->title ?? '#' . $this->getKey());
    }

    protected $fillable = [
        'proposal_id', 'donor_id', 'contract_number', 'title', 'start_date', 'end_date',
        'total_budget', 'currency', 'status', 'signed_contract', 'kickoff_date', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'kickoff_date' => 'date',
        'total_budget' => 'decimal:2',
    ];

    public function proposal() { return $this->belongsTo(Proposal::class); }
    public function donor() { return $this->belongsTo(Donor::class); }
    public function budgets() { return $this->hasMany(AwardBudget::class); }
    public function complianceCalendar() { return $this->hasMany(ComplianceCalendar::class); }
    public function reports() { return $this->hasMany(Report::class); }
    public function monitoringVisits() { return $this->hasMany(MonitoringVisit::class); }
    public function ethicsRenewals() { return $this->hasMany(EthicsRenewal::class); }

    public function getTrafficLightAttribute(): string
    {
        $overdueReports = $this->reports()->where('status', 'pending')->where('due_date', '<', now())->count();
        $upcomingDeadlines = $this->complianceCalendar()->where('status', 'upcoming')->where('due_date', '<=', now()->addDays(14))->count();
        $totalBudget = $this->budgets()->sum('allocated_amount');
        $spent = $this->budgets()->sum('spent_amount');
        $burnRate = $totalBudget > 0 ? ($spent / $totalBudget) * 100 : 0;

        if ($overdueReports > 0 || $burnRate > 110) return 'red';
        if ($upcomingDeadlines > 0 || $burnRate > 90) return 'yellow';
        return 'green';
    }
}
