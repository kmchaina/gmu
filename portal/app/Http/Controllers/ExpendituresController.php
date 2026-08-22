<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\AwardBudget;
use App\Models\Expenditure;
use Illuminate\Http\Request;

/**
 * The expenditure ledger behind each budget line.
 *
 * Budget "spent" figures were typed straight into the budget row, with no
 * transactions behind them — nothing to reconcile at closeout and nothing an
 * auditor could follow. Each entry here is one transaction; the budget line's
 * spent amount is recomputed from them, so the two can never disagree.
 */
class ExpendituresController extends Controller
{
    public function store(Request $request, Award $award)
    {
        $this->authorize('manageBudget', $award);

        $data = $request->validate([
            'award_budget_id'  => 'nullable|exists:award_budgets,id',
            'transaction_date' => 'required|date|before_or_equal:today',
            'description'      => 'required|string|max:255',
            'reference'        => 'nullable|string|max:100',
            'amount'           => 'required|numeric|min:0.01',
            'notes'            => 'nullable|string|max:1000',
        ]);

        // A budget line from another award must not be posted against.
        $line = $data['award_budget_id']
            ? AwardBudget::where('award_id', $award->id)->find($data['award_budget_id'])
            : null;

        if ($data['award_budget_id'] && ! $line) {
            return back()->with('error', 'That budget line does not belong to this award.');
        }

        $award->expenditures()->create([
            'award_budget_id'  => $line?->id,
            'recorded_by'      => auth()->id(),
            'transaction_date' => $data['transaction_date'],
            'description'      => $data['description'],
            'reference'        => $data['reference'] ?? null,
            'amount'           => $data['amount'],
            'currency'         => $line?->currency ?? $award->currency,
            'notes'            => $data['notes'] ?? null,
        ]);

        $this->recalculate($line);

        return back()->with('success', 'Expenditure recorded.');
    }

    public function destroy(Award $award, Expenditure $expenditure)
    {
        $this->authorize('manageBudget', $award);

        $line = $expenditure->budgetLine;
        $expenditure->delete();

        $this->recalculate($line);

        return back()->with('success', 'Expenditure removed.');
    }

    /**
     * Keep the budget line's spent figure equal to its ledger.
     */
    private function recalculate(?AwardBudget $line): void
    {
        if (! $line) {
            return;
        }

        $line->update([
            'spent_amount' => Expenditure::where('award_budget_id', $line->id)->sum('amount'),
        ]);
    }
}
