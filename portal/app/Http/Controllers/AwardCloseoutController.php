<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\AwardCloseout;
use App\Services\Notifier;
use App\Support\Days;
use Illuminate\Http\Request;

/**
 * Closeout & Lessons Learned (GM-BPF Workflow D).
 *
 * The eighth Level-1 process, and the one the audit found missing entirely.
 * An award is not finished when its end date passes — it is finished when the
 * final reports are in, the money is reconciled, the obligations are
 * discharged, the file is archived and the unit has written down what it
 * learned.
 */
class AwardCloseoutController extends Controller
{
    public function show(Award $award)
    {
        $this->authorize('view', $award);

        $award->load('proposal.leadPi', 'donor', 'reports', 'budgets', 'subAwards', 'ethicsRenewals');
        $closeout = $this->closeoutFor($award);

        return view('awards.closeout', [
            'award'    => $award,
            'closeout' => $closeout,
            'canEdit'  => auth()->user()->can('manageCloseout', $award),
            'readiness' => $this->readiness($award),
        ]);
    }

    public function update(Request $request, Award $award)
    {
        $this->authorize('manageCloseout', $award);

        $data = $request->validate([
            'steps'              => 'nullable|array',
            'steps.*'            => 'in:' . implode(',', array_keys(AwardCloseout::STEPS)),
            'total_expenditure'  => 'nullable|numeric|min:0',
            'unspent_balance'    => 'nullable|numeric',
            'funds_returned'     => 'nullable|numeric|min:0',
            'funds_returned_on'  => 'nullable|date',
            'archive_reference'  => 'nullable|string|max:255',
            'what_went_well'     => 'nullable|string|max:5000',
            'what_was_difficult' => 'nullable|string|max:5000',
            'recommendations'    => 'nullable|string|max:5000',
            'donor_feedback'     => 'nullable|string|max:5000',
            'notes'              => 'nullable|string|max:5000',
        ]);

        $closeout = $this->closeoutFor($award);
        $ticked   = $data['steps'] ?? [];

        // A step records when it was done; unticking clears it again.
        foreach (array_keys(AwardCloseout::STEPS) as $column) {
            $wasDone = $closeout->{$column} !== null;
            $isDone  = in_array($column, $ticked, true);

            if ($isDone && ! $wasDone) {
                $closeout->{$column} = now();
            } elseif (! $isDone && $wasDone) {
                $closeout->{$column} = null;
            }
        }

        $closeout->fill([
            'total_expenditure'  => $data['total_expenditure'] ?? $closeout->total_expenditure,
            'unspent_balance'    => $data['unspent_balance'] ?? $closeout->unspent_balance,
            'funds_returned'     => $data['funds_returned'] ?? $closeout->funds_returned,
            'funds_returned_on'  => $data['funds_returned_on'] ?? $closeout->funds_returned_on,
            'archive_reference'  => $data['archive_reference'] ?? $closeout->archive_reference,
            'what_went_well'     => $data['what_went_well'] ?? $closeout->what_went_well,
            'what_was_difficult' => $data['what_was_difficult'] ?? $closeout->what_was_difficult,
            'recommendations'    => $data['recommendations'] ?? $closeout->recommendations,
            'donor_feedback'     => $data['donor_feedback'] ?? $closeout->donor_feedback,
            'notes'              => $data['notes'] ?? $closeout->notes,
        ]);

        if ($closeout->status === 'not_started' && $closeout->completedSteps() > 0) {
            $closeout->status = 'in_progress';
        }

        $closeout->save();

        return back()->with('success', 'Closeout updated — ' . $closeout->progress() . '% complete.');
    }

    /**
     * Sign the closeout off: this is what archives the project (step 4 of the
     * framework) and marks the award completed.
     */
    public function complete(Award $award)
    {
        $this->authorize('manageCloseout', $award);

        $closeout = $this->closeoutFor($award);

        if (! $closeout->readyToComplete()) {
            return back()->with('error', 'Every step must be ticked and the lessons learned recorded before closeout can be signed off.');
        }

        $closeout->update([
            'status'       => 'completed',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        $award->update(['status' => 'completed']);

        app(Notifier::class)->send(
            array_filter([$award->proposal?->leadPi, $award->proposal?->assignedOfficer]),
            'award_closed',
            'Award closed out: ' . $award->contract_number,
            sprintf('"%s" has been closed out and archived. Thank you — the lessons recorded will inform the next proposal.', $award->title),
            $award
        );

        return back()->with('success', 'Closeout signed off. The award is archived and marked completed.');
    }

    private function closeoutFor(Award $award): AwardCloseout
    {
        return AwardCloseout::firstOrCreate(['award_id' => $award->id]);
    }

    /**
     * What the closeout has to work with: the reports still outstanding, the
     * money still unspent, the sub-awards still open.
     */
    private function readiness(Award $award): array
    {
        $outstandingReports = $award->reports->whereIn('status', ['pending', 'rejected'])->count();
        $allocated = (float) $award->budgets->sum('allocated_amount');
        $spent     = (float) $award->budgets->sum('spent_amount');

        return [
            'days_since_end'      => Days::overdueBy($award->end_date),
            'outstanding_reports' => $outstandingReports,
            'open_sub_awards'     => $award->subAwards->whereNotIn('status', ['completed', 'terminated'])->count(),
            'allocated'           => $allocated,
            'spent'               => $spent,
            'balance'             => round($allocated - $spent, 2),
            'ethics_open'         => $award->ethicsRenewals->where('renewal_status', '!=', 'expired')->count(),
        ];
    }
}
