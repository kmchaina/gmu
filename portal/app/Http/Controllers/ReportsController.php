<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Award;
use App\Support\UploadRules;
use App\Support\GrantFiles;
use App\Services\Notifier;
use Illuminate\Http\Request;

class ReportsController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $user  = auth()->user();
        $query = Report::with('award.donor', 'award.proposal.leadPi', 'submittedBy');

        if (! $user->isGmuStaff()) {
            $query->whereHas('award.proposal', function ($q) use ($user) {
                $q->where('lead_pi_id', $user->id)
                  ->orWhereHas('teamMembers', fn($t) => $t->where('user_id', $user->id));
            });
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
        $this->authorize('create', Report::class);

        $awards = Award::with('donor')->where('status', 'active')->orderBy('title')->get();
        $selectedAward = $request->award_id ? Award::with('donor')->find($request->award_id) : null;

        return view('reports.create', compact('awards', 'selectedAward'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Report::class);

        $data = $request->validate([
            'award_id'    => 'required|exists:awards,id',
            'report_type' => 'required|in:technical,financial,combined',
            'title'       => 'required|string|max:255',
            'period_start'=> 'nullable|date',
            'period_end'  => 'nullable|date|after_or_equal:period_start',
            'due_date'    => 'required|date',
            'status'      => 'required|in:pending,submitted,under_review,approved,rejected',
            'file_path'   => UploadRules::office(UploadRules::MAX_LARGE),
            'gmu_comments'=> 'nullable|string',
        ]);

        $data['submitted_by'] = auth()->id();

        if ($request->hasFile('file_path')) {
            $data['file_path'] = GrantFiles::store($request->file('file_path'), 'reports');
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
        $this->authorize('view', $report);

        $report->load('award.donor', 'award.proposal.leadPi', 'submittedBy', 'verifiedBy');
        return view('reports.show', compact('report'));
    }

    public function edit(Report $report)
    {
        $this->authorize('update', $report);

        $awards = Award::with('donor')->where('status', 'active')->orderBy('title')->get();
        return view('reports.edit', compact('report', 'awards'));
    }

    public function update(Request $request, Report $report)
    {
        $this->authorize('update', $report);

        $data = $request->validate([
            'report_type' => 'required|in:technical,financial,combined',
            'title'       => 'required|string|max:255',
            'period_start'=> 'nullable|date',
            'period_end'  => 'nullable|date|after_or_equal:period_start',
            'due_date'    => 'required|date',
            'status'      => 'required|in:pending,submitted,under_review,approved,rejected',
            'file_path'   => UploadRules::office(UploadRules::MAX_LARGE),
            'gmu_comments'=> 'nullable|string',
        ]);

        if ($request->hasFile('file_path')) {
            $data['file_path'] = GrantFiles::replace($request->file('file_path'), 'reports', $report->file_path);
        }

        if (in_array($data['status'], ['submitted', 'under_review', 'approved']) && !$report->submitted_date) {
            $data['submitted_date'] = now()->toDateString();
        }

        // Approving a report is the institutional sign-off before it goes to the
        // donor. The submitter cannot sign off their own (finding H2).
        if ($data['status'] === 'approved' && $report->status !== 'approved') {
            $this->authorize('verify', $report);

            $data['verified_by'] = auth()->id();
            $data['verified_at'] = now();
        }

        $statusChanged = $report->status !== $data['status'];

        $report->update($data);

        if ($statusChanged && in_array($report->status, ['approved', 'rejected'], true)) {
            app(Notifier::class)->reportDecision($report->fresh(['submittedBy', 'award.proposal.leadPi']));
        }

        return redirect()->route('reports.show', $report)
            ->with('success', 'Report updated successfully.');
    }

    /** The submitted report file — award team and GMU officers only. */
    public function download(Report $report)
    {
        $this->authorize('view', $report);

        return GrantFiles::download($report->file_path);
    }

    public function destroy(Report $report)
    {
        $this->authorize('delete', $report);

        $report->delete();
        return redirect()->route('reports.index')
            ->with('success', 'Report removed.');
    }
}
