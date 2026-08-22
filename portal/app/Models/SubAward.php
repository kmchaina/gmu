<?php

namespace App\Models;

use App\Models\Concerns\ConvertsToBaseCurrency;
use App\Support\Days;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Money passed through to a partner institution, and the compliance that comes
 * with it.
 */
class SubAward extends Model
{
    use SoftDeletes, LogsActivity, ConvertsToBaseCurrency;

    public const PARTNER_TYPES = [
        'institution' => 'Research institution',
        'university'  => 'University',
        'ngo'         => 'NGO',
        'cbo'         => 'Community organisation',
        'government'  => 'Government body',
        'private'     => 'Private sector',
    ];

    protected $fillable = [
        'award_id', 'partner_name', 'partner_type', 'country', 'contact_person', 'contact_email',
        'agreement_number', 'agreement_file', 'amount', 'currency', 'exchange_rate', 'base_amount',
        'disbursed_amount', 'start_date', 'end_date', 'status', 'reporting_frequency',
        'next_report_due', 'due_diligence_completed_on', 'last_monitored_on', 'scope_of_work', 'notes',
    ];

    protected array $baseCurrencyMap = ['amount' => 'base_amount'];

    protected $casts = [
        'start_date'                 => 'date',
        'end_date'                   => 'date',
        'next_report_due'            => 'date',
        'due_diligence_completed_on' => 'date',
        'last_monitored_on'          => 'date',
        'amount'                     => 'decimal:2',
        'base_amount'                => 'decimal:2',
        'disbursed_amount'           => 'decimal:2',
    ];

    public function auditLabel(): string
    {
        return 'Sub-award to ' . $this->partner_name;
    }

    public function award() { return $this->belongsTo(Award::class); }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['completed', 'terminated'], true);
    }

    /** Green / amber / red, on the same logic as the parent award. */
    public function health(): string
    {
        if ($this->due_diligence_completed_on === null && $this->status === 'active') {
            return 'red';
        }

        if ($this->next_report_due && Days::isPast($this->next_report_due)) {
            return 'red';
        }

        $monitoredRecently = $this->last_monitored_on && Days::overdueBy($this->last_monitored_on) <= 180;

        if ($this->status === 'active' && ! $monitoredRecently) {
            return 'yellow';
        }

        return 'green';
    }

    public function disbursedPercentage(): float
    {
        return $this->amount > 0
            ? round(($this->disbursed_amount / $this->amount) * 100, 1)
            : 0.0;
    }
}
