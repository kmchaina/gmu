<?php

namespace App\Http\Controllers;

use App\Models\DonorPolicy;
use App\Models\Donor;
use App\Support\UploadRules;
use Illuminate\Http\Request;

class DonorPoliciesController extends Controller
{
    public function index(Request $request)
    {
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
        $donors = Donor::orderBy('name')->get();
        $selectedDonor = $request->donor_id ? Donor::find($request->donor_id) : null;
        return view('donor-policies.create', compact('donors', 'selectedDonor'));
    }

    public function store(Request $request)
    {
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
        $path = $file->store('donor-policies', 'public');

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
        return redirect(\Storage::url($donorPolicy->file_path));
    }

    public function edit(DonorPolicy $donorPolicy)
    {
        $donors = Donor::orderBy('name')->get();
        return view('donor-policies.edit', compact('donorPolicy', 'donors'));
    }

    public function update(Request $request, DonorPolicy $donorPolicy)
    {
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
            $data['file_path'] = $file->store('donor-policies', 'public');
            $data['file_name'] = $file->getClientOriginalName();
        }

        $donorPolicy->update($data);

        return redirect()->route('donor-policies.index')
            ->with('success', 'Policy updated.');
    }

    public function destroy(DonorPolicy $donorPolicy)
    {
        $donorPolicy->delete();
        return redirect()->route('donor-policies.index')
            ->with('success', 'Policy removed.');
    }
}
