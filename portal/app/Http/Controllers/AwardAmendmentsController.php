<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\AwardAmendment;
use App\Services\ComplianceScheduleGenerator;
use App\Services\Notifier;
use App\Support\GrantFiles;
use App\Support\UploadRules;
use Illuminate\Http\Request;

/**
 * Amendments and no-cost extensions.
 *
 * Recording the donor approval is what applies the change to the award: the
 * end date moves, the budget is revised, and — for an extension — the
 * reporting calendar is extended to match, so the deadlines a PI is chased
 * against stay true to the contract.
 */
class AwardAmendmentsController extends Controller
{
    public function store(Request $request, Award $award)
    {
        $this->authorize('update', $award);

        $data = $request->validate([
            'type'              => 'required|in:' . implode(',', array_keys(AwardAmendment::TYPES)),
            'reference'         => 'nullable|string|max:100',
            'reason'            => 'required|string|max:2000',
            'new_end_date'      => 'nullable|date|after:' . $award->start_date->toDateString(),
            'new_budget'        => 'nullable|numeric|min:0',
            'requested_on'      => 'nullable|date',
            'donor_approved_on' => 'nullable|date',
            'status'            => 'required|in:draft,submitted,approved,rejected',
            'notes'             => 'nullable|string|max:2000',
            'approval_file'     => UploadRules::office(),
        ]);

        $amendment = $award->amendments()->create($data + [
            'recorded_by'       => auth()->id(),
            'previous_end_date' => $award->end_date,
            'previous_budget'   => $award->total_budget,
        ]);

        if ($request->hasFile('approval_file')) {
            $amendment->update([
                'approval_file' => GrantFiles::store($request->file('approval_file'), 'award-amendments'),
            ]);
        }

        if ($amendment->status === 'approved') {
            $this->apply($award, $amendment);
        }

        return back()->with('success', $amendment->typeName() . ' recorded.');
    }

    /**
     * Approving an amendment later is the same event, so the change is applied
     * at that point instead.
     */
    public function update(Request $request, Award $award, AwardAmendment $amendment)
    {
        $this->authorize('update', $award);

        $data = $request->validate([
            'status'            => 'required|in:draft,submitted,approved,rejected',
            'donor_approved_on' => 'nullable|date',
            'notes'             => 'nullable|string|max:2000',
        ]);

        $wasApproved = $amendment->status === 'approved';
        $amendment->update($data);

        if (! $wasApproved && $amendment->status === 'approved') {
            $this->apply($award, $amendment);
        }

        return back()->with('success', 'Amendment updated.');
    }

    public function downloadApproval(Award $award, AwardAmendment $amendment)
    {
        $this->authorize('view', $award);

        return GrantFiles::download($amendment->approval_file);
    }

    public function destroy(Award $award, AwardAmendment $amendment)
    {
        $this->authorize('update', $award);

        GrantFiles::delete($amendment->approval_file);
        $amendment->delete();

        return back()->with('success', 'Amendment removed.');
    }

    /**
     * Push the approved change onto the award itself.
     */
    private function apply(Award $award, AwardAmendment $amendment): void
    {
        $changes = [];

        if ($amendment->new_end_date) {
            $changes['end_date'] = $amendment->new_end_date;
        }

        if ($amendment->new_budget !== null) {
            $changes['total_budget'] = $amendment->new_budget;
        }

        if ($changes === []) {
            return;
        }

        $award->update($changes);

        // An extension means more reporting periods, so the calendar follows it.
        if ($amendment->new_end_date) {
            $award->forceFill(['schedule_generated' => false])->save();
            app(ComplianceScheduleGenerator::class)->generate($award->fresh());
        }

        app(Notifier::class)->send(
            array_filter([$award->proposal?->leadPi, $award->proposal?->assignedOfficer]),
            'award_amended',
            $amendment->typeName() . ': ' . $award->contract_number,
            sprintf(
                '%s has been applied. %s%s',
                $amendment->typeName(),
                $amendment->new_end_date ? 'The award now ends ' . $amendment->new_end_date->format('j M Y') . '. ' : '',
                $amendment->new_budget !== null
                    ? 'The value is now ' . $award->currency . ' ' . number_format((float) $amendment->new_budget, 2) . '.'
                    : ''
            ),
            $award
        );
    }
}
