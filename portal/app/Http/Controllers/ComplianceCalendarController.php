<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\ComplianceCalendar;
use Illuminate\Http\Request;

class ComplianceCalendarController extends Controller
{
    public function store(Request $request, Award $award)
    {
        $this->authorize('manageCompliance', $award);

        $request->validate([
            'title'            => 'required|string|max:255',
            'deliverable_type' => 'required|string|max:100',
            'due_date'         => 'required|date',
            'notes'            => 'nullable|string',
        ]);

        $award->complianceCalendar()->create([
            'title'            => $request->title,
            'deliverable_type' => $request->deliverable_type,
            'due_date'         => $request->due_date,
            'status'           => 'upcoming',
            'notes'            => $request->notes,
        ]);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Compliance event added.');
    }

    public function update(Request $request, Award $award, ComplianceCalendar $item)
    {
        $this->authorize('manageCompliance', $award);

        $request->validate([
            'status' => 'required|in:upcoming,submitted,overdue,waived',
        ]);

        $item->update([
            'status'       => $request->status,
            'submitted_at' => $request->status === 'submitted' ? now() : $item->submitted_at,
            'submitted_by' => $request->status === 'submitted' ? auth()->id() : $item->submitted_by,
        ]);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Compliance event updated.');
    }

    public function destroy(Award $award, ComplianceCalendar $item)
    {
        $this->authorize('manageCompliance', $award);

        $item->delete();
        return redirect()->route('awards.show', $award)
            ->with('success', 'Compliance event removed.');
    }
}
