<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * The closeout record for an award — Workflow D of the business process
 * framework, which had no representation in the system before Phase 3.
 */
class AwardCloseout extends Model
{
    use LogsActivity;

    /**
     * The steps that make up a completed closeout, in the order the framework
     * puts them. The key is the column that records when the step was done.
     */
    public const STEPS = [
        'final_technical_report_at' => 'Final technical report received',
        'final_financial_report_at' => 'Final financial statement received',
        'financial_reconciled_at'   => 'Expenditure reconciled and balance agreed',
        'obligations_completed_at'  => 'Contractual obligations completed',
        'sub_awards_closed_at'      => 'Sub-awards closed out',
        'assets_transferred_at'     => 'Equipment and assets dealt with',
        'ethics_closed_at'          => 'Ethics approval closed with the committee',
        'archived_at'               => 'Project file archived',
    ];

    protected $fillable = [
        'award_id', 'status',
        'final_technical_report_at', 'final_financial_report_at', 'financial_reconciled_at',
        'obligations_completed_at', 'assets_transferred_at', 'ethics_closed_at', 'sub_awards_closed_at',
        'total_expenditure', 'unspent_balance', 'funds_returned', 'funds_returned_on',
        'archived_at', 'archive_reference',
        'what_went_well', 'what_was_difficult', 'recommendations', 'donor_feedback',
        'completed_by', 'completed_at', 'notes',
    ];

    protected $casts = [
        'final_technical_report_at' => 'datetime',
        'final_financial_report_at' => 'datetime',
        'financial_reconciled_at'   => 'datetime',
        'obligations_completed_at'  => 'datetime',
        'assets_transferred_at'     => 'datetime',
        'ethics_closed_at'          => 'datetime',
        'sub_awards_closed_at'      => 'datetime',
        'archived_at'               => 'datetime',
        'completed_at'              => 'datetime',
        'funds_returned_on'         => 'date',
        'total_expenditure'         => 'decimal:2',
        'unspent_balance'           => 'decimal:2',
        'funds_returned'            => 'decimal:2',
    ];

    public function auditLabel(): string
    {
        return 'Closeout for award ' . ($this->award?->contract_number ?? '#' . $this->award_id);
    }

    public function award() { return $this->belongsTo(Award::class); }
    public function completedBy() { return $this->belongsTo(User::class, 'completed_by'); }

    /** Steps done, out of the full list. */
    public function completedSteps(): int
    {
        return collect(array_keys(self::STEPS))
            ->filter(fn($column) => $this->{$column} !== null)
            ->count();
    }

    public function progress(): int
    {
        return (int) round(($this->completedSteps() / count(self::STEPS)) * 100);
    }

    /** Lessons learned are the point of the exercise, not an optional extra. */
    public function hasLessons(): bool
    {
        return filled($this->what_went_well) || filled($this->what_was_difficult) || filled($this->recommendations);
    }

    /** Everything the framework asks for before an award can be signed off. */
    public function readyToComplete(): bool
    {
        return $this->completedSteps() === count(self::STEPS) && $this->hasLessons();
    }

    /** What is still outstanding, for the screen and for the digest. */
    public function outstandingSteps(): array
    {
        return collect(self::STEPS)
            ->reject(fn($label, $column) => $this->{$column} !== null)
            ->values()
            ->all();
    }
}
