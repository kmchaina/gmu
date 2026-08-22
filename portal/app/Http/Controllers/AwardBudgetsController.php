<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\AwardBudget;
use Illuminate\Http\Request;

class AwardBudgetsController extends Controller
{
    public function store(Request $request, Award $award)
    {
        $request->validate([
            'category'         => 'required|string|max:100',
            'allocated_amount' => 'required|numeric|min:0',
            'spent_amount'     => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $award->budgets()->create([
            'category'         => $request->category,
            'allocated_amount' => $request->allocated_amount,
            'spent_amount'     => $request->spent_amount ?? 0,
            'currency'         => $award->currency,
            'notes'            => $request->notes,
        ]);

        return redirect()->route('awards.show', $award)
            ->with('success', 'Budget line added.');
    }

    public function destroy(Award $award, AwardBudget $budget)
    {
        $budget->delete();
        return redirect()->route('awards.show', $award)
            ->with('success', 'Budget line removed.');
    }
}
