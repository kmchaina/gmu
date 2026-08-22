<?php

namespace App\Models;

use App\Traits\LogsActivity;
use App\Models\Concerns\ConvertsToBaseCurrency;
use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes, LogsActivity, ConvertsToBaseCurrency;

    public function auditLabel(): string
    {
        return 'Award ' . ($this->contract_number ?? $this->title ?? '#' . $this->getKey());
    }

    protected $fillable = [
        'proposal_id', 'donor_id', 'contract_number', 'title', 'start_date', 'end_date',
        'total_budget', 'currency', 'status', 'signed_contract', 'kickoff_date', 'notes',
        'exchange_rate', 'base_total_budget', 'reporting_frequency', 'schedule_generated',
    ];

    /** Contract amount => reporting-currency equivalent (finding M3). */
    protected array $baseCurrencyMap = ['total_budget' => 'base_total_budget'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'kickoff_date' => 'date',
        'total_budget' => 'decimal:2',
        'base_total_budget' => 'decimal:2',
        'schedule_generated' => 'boolean',
    ];

    public function proposal() { return $this->belongsTo(Proposal::class); }
    public function donor() { return $this->belongsTo(Donor::class); }
    public function budgets() { return $this->hasMany(AwardBudget::class); }
    public function complianceCalendar() { return $this->hasMany(ComplianceCalendar::class); }
    public function reports() { return $this->hasMany(Report::class); }
    public function monitoringVisits() { return $this->hasMany(MonitoringVisit::class); }
    public function ethicsRenewals() { return $this->hasMany(EthicsRenewal::class); }
    public function subAwards() { return $this->hasMany(SubAward::class); }
    public function expenditures() { return $this->hasMany(Expenditure::class); }
    public function closeout() { return $this->hasOne(AwardCloseout::class); }

    /** Money passed through to partners, in the reporting currency. */
    public function subAwardedTotal(): float
    {
        return (float) $this->subAwards()->whereIn('status', ['active', 'completed'])->sum('base_amount');
    }

    /**
     * Load everything the traffic light needs in one query per aggregate.
     *
     * Finding M10: the accessor below ran four queries per award, once per row
     * in the awards list and again on the dashboard — around sixty queries to
     * draw one page. Listings now call this scope and the accessor reads the
     * loaded values.
     */
    public function scopeWithHealth($query)
    {
        return $query
            ->withCount([
                'reports as overdue_reports_count' => fn($q) => $q->where('status', 'pending')->whereDate('due_date', '<', now()),
                'complianceCalendar as due_soon_count' => fn($q) => $q->whereIn('status', ['upcoming', 'overdue'])->whereDate('due_date', '<=', now()->addDays(14)),
            ])
            ->withSum('budgets as allocated_total', 'allocated_amount')
            ->withSum('budgets as spent_total', 'spent_amount');
    }

    public function getTrafficLightAttribute(): string
    {
        $overdueReports = $this->overdue_reports_count
            ?? $this->reports()->where('status', 'pending')->whereDate('due_date', '<', now())->count();

        $upcomingDeadlines = $this->due_soon_count
            ?? $this->complianceCalendar()->whereIn('status', ['upcoming', 'overdue'])->whereDate('due_date', '<=', now()->addDays(14))->count();

        $allocated = $this->allocated_total ?? $this->budgets()->sum('allocated_amount');
        $spent     = $this->spent_total ?? $this->budgets()->sum('spent_amount');
        $burnRate  = $allocated > 0 ? ($spent / $allocated) * 100 : 0;

        if ($overdueReports > 0 || $burnRate > 110) return 'red';
        if ($upcomingDeadlines > 0 || $burnRate > 90) return 'yellow';
        return 'green';
    }

    /** Percentage of the allocated budget spent so far. */
    public function burnRate(): float
    {
        $allocated = (float) ($this->allocated_total ?? $this->budgets()->sum('allocated_amount'));
        $spent     = (float) ($this->spent_total ?? $this->budgets()->sum('spent_amount'));

        return $allocated > 0 ? round(($spent / $allocated) * 100, 1) : 0.0;
    }
}
