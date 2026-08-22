<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Award;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = Report::with('award.donor', 'award.proposal.leadPi', 'submittedBy');

        if ($user->isPI() || $user->isResearchScientist()) {
            $query->whereHas('award.proposal', fn($q) => $q->where('lead_pi_id', $user->id));
        }

        if ($request->award_id) {
            $query->where('award_id', $request->award_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('report_type', $request->type);
        }

        $reports = $query->orderBy('due_date')->paginate(20)->withQueryString();

        return view('reports.index', compact('reports'));
    }

    public function create(Request $request)
    {
        $awards = Award::with('donor')->where('status', 'active')->orderBy('title')->get();
        $selectedAward = $request->award_id ? Award::with('donor')->find($request->award_id) : null;

        return view('reports.create', compact('awards', 'selectedAward'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'award_id'    => 'required|exists:awards,id',
            'report_type' => 'required|in:technical,financial,combined',
            'title'       => 'required|string|max:255',
            'period_start'=> 'nullable|date',
            'period_end'  => 'nullable|date|after_or_equal:period_start',
            'due_date'    => 'required|date',
            'status'      => 'required|in:pending,submitted,under_review,approved,rejected',
            'file_path'   => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:20480',
            'gmu_comments'=> 'nullable|string',
        ]);

        $data['submitted_by'] = auth()->id();

        if ($request->hasFile('file_path')) {
            $data['file_path'] = $request->file('file_path')->store('reports', 'public');
            if (in_array($data['status'], ['submitted', 'under_review', 'approved'])) {
                $data['submitted_date'] = now()->toDateString();
            }
        }

        $report = Report::create($data);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Report recorded successfully.');
    }

    public function show(Report $report)
    {
        $report->load('award.donor', 'award.proposal.leadPi', 'submittedBy', 'verifiedBy');
        return view('reports.show', compact('report'));
    }

    public function edit(Report $report)
    {
        $awards = Award::with('donor')->where('status', 'active')->orderBy('title')->get();
        return view('reports.edit', compact('report', 'awards'));
    }

    public function update(Request $request, Report $report)
    {
        $data = $request->validate([
            'report_type' => 'required|in:technical,financial,combined',
            'title'       => 'required|string|max:255',
            'period_start'=> 'nullable|date',
            'period_end'  => 'nullable|date|after_or_equal:period_start',
            'due_date'    => 'required|date',
            'status'      => 'required|in:pending,submitted,under_review,approved,rejected',
            'file_path'   => 'nullable|file|mimes:pdf,doc,docx,xlsx|max:20480',
            'gmu_comments'=> 'nullable|string',
        ]);

        if ($request->hasFile('file_path')) {
            $data['file_path'] = $request->file('file_path')->store('reports', 'public');
        }

        if (in_array($data['status'], ['submitted', 'under_review', 'approved']) && !$report->submitted_date) {
            $data['submitted_date'] = now()->toDateString();
        }

        if ($data['status'] === 'approved' && !$report->verified_at) {
            $data['verified_by'] = auth()->id();
            $data['verified_at'] = now();
        }

        $report->update($data);

        return redirect()->route('reports.show', $report)
            ->with('success', 'Report updated successfully.');
    }

    public function destroy(Report $report)
    {
        $report->delete();
        return redirect()->route('reports.index')
            ->with('success', 'Report removed.');
    }
}
