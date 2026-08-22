<?php

namespace App\Http\Controllers;

use App\Models\DonorPolicy;
use App\Models\Donor;
use App\Support\UploadRules;
use App\Support\GrantFiles;
use Illuminate\Http\Request;

class DonorPoliciesController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', DonorPolicy::class);

        $query = DonorPolicy::with('donor', 'uploadedBy');

        if ($request->donor_id) {
            $query->where('donor_id', $request->donor_id);
        }

        if ($request->policy_type) {
            $query->where('policy_type', $request->policy_type);
        }

        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $policies = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $donors   = Donor::orderBy('name')->get();

        return view('donor-policies.index', compact('policies', 'donors'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', DonorPolicy::class);

        $donors = Donor::orderBy('name')->get();
        $selectedDonor = $request->donor_id ? Donor::find($request->donor_id) : null;
        return view('donor-policies.create', compact('donors', 'selectedDonor'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', DonorPolicy::class);

        $data = $request->validate([
            'donor_id'       => 'required|exists:donors,id',
            'title'          => 'required|string|max:255',
            'policy_type'    => 'required|in:grant_guidelines,reporting_template,budget_template,compliance_policy,other',
            'version'        => 'required|integer|min:1',
            'effective_date' => 'nullable|date',
            'notes'          => 'nullable|string',
            'file'           => UploadRules::officeRequired(UploadRules::MAX_LARGE),
        ]);

        $file = $request->file('file');
        $path = GrantFiles::store($file, 'donor-policies');

        DonorPolicy::create([
            'donor_id'       => $data['donor_id'],
            'uploaded_by'    => auth()->id(),
            'title'          => $data['title'],
            'policy_type'    => $data['policy_type'],
            'file_path'      => $path,
            'file_name'      => $file->getClientOriginalName(),
            'version'        => $data['version'],
            'effective_date' => $data['effective_date'] ?? null,
            'notes'          => $data['notes'] ?? null,
        ]);

        return redirect()->route('donor-policies.index')
            ->with('success', 'Policy document uploaded successfully.');
    }

    public function show(DonorPolicy $donorPolicy)
    {
        $this->authorize('view', $donorPolicy);

        $this->authorize('download', $donorPolicy);

        return GrantFiles::download($donorPolicy->file_path, $donorPolicy->file_name);
    }

    public function edit(DonorPolicy $donorPolicy)
    {
        $this->authorize('update', $donorPolicy);

        $donors = Donor::orderBy('name')->get();
        return view('donor-policies.edit', compact('donorPolicy', 'donors'));
    }

    public function update(Request $request, DonorPolicy $donorPolicy)
    {
        $this->authorize('update', $donorPolicy);

        $data = $request->validate([
            'donor_id'       => 'required|exists:donors,id',
            'title'          => 'required|string|max:255',
            'policy_type'    => 'required|in:grant_guidelines,reporting_template,budget_template,compliance_policy,other',
            'version'        => 'required|integer|min:1',
            'effective_date' => 'nullable|date',
            'notes'          => 'nullable|string',
            'file'           => UploadRules::office(UploadRules::MAX_LARGE),
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['file_path'] = GrantFiles::replace($file, 'donor-policies', $donorPolicy->file_path);
            $data['file_name'] = $file->getClientOriginalName();
        }

        $donorPolicy->update($data);

        return redirect()->route('donor-policies.index')
            ->with('success', 'Policy updated.');
    }

    public function destroy(DonorPolicy $donorPolicy)
    {
        $this->authorize('delete', $donorPolicy);

        GrantFiles::delete($donorPolicy->file_path);
        $donorPolicy->delete();
        return redirect()->route('donor-policies.index')
            ->with('success', 'Policy removed.');
    }
}
