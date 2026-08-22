<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\EthicsRenewal;
use App\Support\UploadRules;
use Illuminate\Http\Request;

class EthicsRenewalsController extends Controller
{
    public function store(Request $request, Award $award)
    {
        $request->validate([
            'ethics_number'    => 'required|string|max:100',
            'issuing_body'     => 'nullable|string|max:255',
            'approval_date'    => 'nullable|date',
            'expiry_date'      => 'required|date',
            'renewal_status'   => 'required|in:valid,renewal_due,expired,renewed',
            'certificate_file' => UploadRules::office(),
        ]);

        $data = $request->only(['ethics_number', 'issuing_body', 'approval_date', 'expiry_date', 'renewal_status']);

        if ($request->hasFile('certificate_file')) {
            $data['certificate_file'] = $request->file('certificate_file')->store('ethics-certificates', 'public');
        }

        $award->ethicsRenewals()->create($data);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Ethics / protocol record added.');
    }

    public function update(Request $request, Award $award, EthicsRenewal $renewal)
    {
        $request->validate([
            'renewal_status'   => 'required|in:valid,renewal_due,expired,renewed',
            'expiry_date'      => 'required|date',
            'issuing_body'     => 'nullable|string|max:255',
            'approval_date'    => 'nullable|date',
            'certificate_file' => UploadRules::office(),
        ]);

        $data = $request->only(['renewal_status', 'expiry_date', 'issuing_body', 'approval_date']);

        if ($request->hasFile('certificate_file')) {
            $data['certificate_file'] = $request->file('certificate_file')->store('ethics-certificates', 'public');
        }

        $renewal->update($data);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Ethics record updated.');
    }

    public function destroy(Award $award, EthicsRenewal $renewal)
    {
        $renewal->delete();
        return redirect()->route('awards.show', $award)
            ->with('success', 'Ethics record removed.');
    }
}
