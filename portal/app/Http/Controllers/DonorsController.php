<?php

namespace App\Http\Controllers;

use App\Models\Donor;
use Illuminate\Http\Request;

class DonorsController extends Controller
{
    public function index(Request $request)
    {
        $query = Donor::withCount('fundingCalls', 'awards', 'policies');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('acronym', 'like', "%{$request->search}%")
                  ->orWhere('country', 'like', "%{$request->search}%");
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $donors = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('donors.index', compact('donors'));
    }

    public function create()
    {
        return view('donors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'acronym'       => 'nullable|string|max:20',
            'type'          => 'required|in:bilateral,multilateral,foundation,government,private,other',
            'country'       => 'nullable|string|max:100',
            'website'       => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'notes'         => 'nullable|string',
        ]);

        $donor = Donor::create($data);

        if ($request->expectsJson()) {
            return response()->json(['id' => $donor->id, 'name' => $donor->name]);
        }

        return redirect()->route('donors.show', $donor)
            ->with('success', 'Donor added successfully.');
    }

    public function show(Donor $donor)
    {
        $donor->load([
            'fundingCalls' => fn($q) => $q->orderBy('deadline', 'desc')->limit(10),
            'awards'       => fn($q) => $q->with('proposal.leadPi')->orderBy('created_at', 'desc')->limit(10),
            'policies'     => fn($q) => $q->orderBy('created_at', 'desc')->limit(10),
        ]);

        return view('donors.show', compact('donor'));
    }

    public function edit(Donor $donor)
    {
        return view('donors.edit', compact('donor'));
    }

    public function update(Request $request, Donor $donor)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'acronym'       => 'nullable|string|max:20',
            'type'          => 'required|in:bilateral,multilateral,foundation,government,private,other',
            'country'       => 'nullable|string|max:100',
            'website'       => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'notes'         => 'nullable|string',
        ]);

        $donor->update($data);

        return redirect()->route('donors.show', $donor)
            ->with('success', 'Donor updated successfully.');
    }

    public function destroy(Donor $donor)
    {
        if ($donor->fundingCalls()->count() > 0 || $donor->awards()->count() > 0) {
            return redirect()->route('donors.show', $donor)
                ->with('error', 'Cannot delete donor — they have linked funding calls or awards.');
        }

        $donor->delete();

        return redirect()->route('donors.index')
            ->with('success', 'Donor removed.');
    }
}
