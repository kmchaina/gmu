<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\MonitoringVisit;
use App\Support\UploadRules;
use App\Support\GrantFiles;
use Illuminate\Http\Request;

class MonitoringVisitsController extends Controller
{
    public function store(Request $request, Award $award)
    {
        $this->authorize('manageMonitoring', $award);

        $request->validate([
            'visit_date'      => 'required|date',
            'location'        => 'nullable|string|max:255',
            'findings'        => 'required|string|min:20',
            'recommendations' => 'nullable|string',
            'report_file'     => UploadRules::office(),
        ]);

        $data = [
            'visit_date'      => $request->visit_date,
            'location'        => $request->location,
            'findings'        => $request->findings,
            'recommendations' => $request->recommendations,
            'conducted_by'    => auth()->id(),
        ];

        if ($request->hasFile('report_file')) {
            $data['report_file'] = GrantFiles::store($request->file('report_file'), 'monitoring-visits');
        }

        $award->monitoringVisits()->create($data);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Monitoring visit logged.');
    }

    /** The visit report — award team and GMU officers. */
    public function downloadReport(Award $award, MonitoringVisit $visit)
    {
        $this->authorize('view', $award);

        return GrantFiles::download($visit->report_file);
    }

    public function destroy(Award $award, MonitoringVisit $visit)
    {
        $this->authorize('manageMonitoring', $award);

        GrantFiles::delete($visit->report_file);
        $visit->delete();
        return redirect()->route('awards.show', $award)
            ->with('success', 'Visit record removed.');
    }
}
