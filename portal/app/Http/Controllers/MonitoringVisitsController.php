<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\MonitoringVisit;
use App\Support\UploadRules;
use Illuminate\Http\Request;

class MonitoringVisitsController extends Controller
{
    public function store(Request $request, Award $award)
    {
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
            $data['report_file'] = $request->file('report_file')->store('monitoring-visits', 'public');
        }

        $award->monitoringVisits()->create($data);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Monitoring visit logged.');
    }

    public function destroy(Award $award, MonitoringVisit $visit)
    {
        $visit->delete();
        return redirect()->route('awards.show', $award)
            ->with('success', 'Visit record removed.');
    }
}
