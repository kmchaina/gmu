<?php

namespace App\Models;

use App\Support\Days;
use App\Traits\LogsActivity;
use App\Models\Concerns\ConvertsToBaseCurrency;
use Illuminate\Database\Eloquent\Model;

class FundingCall extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes, LogsActivity, ConvertsToBaseCurrency;

    public function auditLabel(): string
    {
        return 'Call: ' . ($this->title ?? '#' . $this->getKey());
    }

    protected $fillable = [
        'title', 'donor_id', 'posted_by', 'description', 'theme', 'eligibility',
        'solicitation_number', 'estimated_budget', 'currency', 'open_date', 'deadline',
        'initiation_type', 'status', 'call_document',
        'exchange_rate', 'base_estimated_budget',
    ];

    /** Indicative budget => reporting-currency equivalent (finding M3). */
    protected array $baseCurrencyMap = ['estimated_budget' => 'base_estimated_budget'];

    protected $casts = [
        'deadline' => 'date',
        'open_date' => 'date',
        'estimated_budget' => 'decimal:2',
    ];

    public function donor() { return $this->belongsTo(Donor::class); }
    public function postedBy() { return $this->belongsTo(User::class, 'posted_by'); }
    public function interests() { return $this->hasMany(CallInterest::class); }
    public function proposals() { return $this->hasMany(Proposal::class); }

    public function getDaysUntilDeadlineAttribute(): int
    {
        return Days::until($this->deadline);
    }

    public function isClosingSoon(): bool
    {
        return $this->days_until_deadline <= 30 && $this->days_until_deadline >= 0;
    }
}
