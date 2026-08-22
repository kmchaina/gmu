<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\SubAward;
use App\Services\Notifier;
use App\Support\GrantFiles;
use App\Support\Money;
use App\Support\UploadRules;
use Illuminate\Http\Request;

/**
 * Sub-awards to partner institutions.
 *
 * NIMR remains accountable to the donor for money it passes on, so the record
 * carries the compliance obligations too: due diligence before the first
 * disbursement, a reporting rhythm, and a monitoring date.
 */
class SubAwardsController extends Controller
{
    public function store(Request $request, Award $award)
    {
        $this->authorize('manageSubAwards', $award);

        $data = $this->validated($request);

        $subAward = $award->subAwards()->create($data + [
            'currency' => $data['currency'] ?? $award->currency,
        ]);

        if ($request->hasFile('agreement_file')) {
            $subAward->update([
                'agreement_file' => GrantFiles::store($request->file('agreement_file'), 'sub-award-agreements'),
            ]);
        }

        app(Notifier::class)->gmuOffice(
            'sub_award_created',
            'Sub-award recorded: ' . $subAward->partner_name,
            sprintf(
                '%s under %s, %s. Due diligence must be completed before the first disbursement.',
                Money::format($subAward->amount, $subAward->currency),
                $award->contract_number,
                $subAward->partner_name
            ),
            $subAward
        );

        return back()->with('success', 'Sub-award recorded.');
    }

    public function update(Request $request, Award $award, SubAward $subAward)
    {
        $this->authorize('manageSubAwards', $award);

        $data = $this->validated($request);

        if ($request->hasFile('agreement_file')) {
            $data['agreement_file'] = GrantFiles::replace(
                $request->file('agreement_file'),
                'sub-award-agreements',
                $subAward->agreement_file
            );
        }

        $subAward->update($data);

        return back()->with('success', 'Sub-award updated.');
    }

    public function downloadAgreement(Award $award, SubAward $subAward)
    {
        $this->authorize('view', $award);

        return GrantFiles::download($subAward->agreement_file);
    }

    public function destroy(Award $award, SubAward $subAward)
    {
        $this->authorize('manageSubAwards', $award);

        $subAward->delete();

        return back()->with('success', 'Sub-award removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'partner_name'               => 'required|string|max:255',
            'partner_type'               => 'required|in:' . implode(',', array_keys(SubAward::PARTNER_TYPES)),
            'country'                    => 'nullable|string|max:100',
            'contact_person'             => 'nullable|string|max:255',
            'contact_email'              => 'nullable|email|max:255',
            'agreement_number'           => 'nullable|string|max:100',
            'amount'                     => 'required|numeric|min:0',
            'currency'                   => 'nullable|string|max:10',
            'disbursed_amount'           => 'nullable|numeric|min:0',
            'start_date'                 => 'required|date',
            'end_date'                   => 'required|date|after:start_date',
            'status'                     => 'required|in:draft,active,completed,suspended,terminated',
            'reporting_frequency'        => 'required|in:monthly,quarterly,semi_annual,annual',
            'next_report_due'            => 'nullable|date',
            'due_diligence_completed_on' => 'nullable|date',
            'last_monitored_on'          => 'nullable|date',
            'scope_of_work'              => 'nullable|string|max:2000',
            'notes'                      => 'nullable|string|max:2000',
            'agreement_file'             => UploadRules::office(),
        ]);
    }
}
