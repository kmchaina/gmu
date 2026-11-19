<?php

namespace App\Http\Controllers\SeedGrant;

use App\Http\Controllers\Controller;
use App\Models\Seed\SeedApplication;
use App\Models\Seed\SeedCall;
use App\Services\SeedGrant\Workflow;
use App\Support\SeedGrant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Full proposals ranked by the reviewers' mean score, ties broken on science,
 * then feasibility, then relevance, with the funding threshold drawn across.
 */
class RankingController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage-seed-grants') || $request->user()->can('approve-seed-grants'), 403);

        $call = SeedCall::current();
        abort_unless($call, 404);

        $ranked = self::rank(SeedApplication::with(['pi', 'budgetItems', 'rounds.reviews'])
            ->where('seed_call_id', $call->id)
            ->whereNotIn('status', SeedGrant::CONCEPT_STATUSES)
            ->get());

        $recommended = $ranked->where('application.status', '!=', SeedGrant::NOT_RECOMMENDED)
            ->filter(fn ($row) => $row['passes']);

        return view('seed.ranking', [
            'call'        => $call,
            'ranked'      => $ranked,
            'recommendedTotal' => $recommended->sum(fn ($r) => $r['application']->budgetTotal('proposal')),
            'recommendedCount' => $recommended->count(),
            'awaitingCount'    => $ranked->where('application.status', SeedGrant::RECOMMENDED)->count(),
        ]);
    }

    /** GMU signs off every recommended proposal ticked, sending them to the DRCP. */
    public function send(Request $request, Workflow $workflow)
    {
        abort_unless($request->user()->can('manage-seed-grants'), 403);

        $data = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']], ['ids.required' => 'Tick at least one recommended proposal.']);

        $sent = 0;
        SeedApplication::whereIn('id', $data['ids'])->where('status', SeedGrant::RECOMMENDED)->get()
            ->each(function (SeedApplication $application) use ($workflow, $request, &$sent) {
                $workflow->approve($application, $request->user(), 'gmu', 'approved', null);
                $sent++;
            });

        return back()->with('success', $sent . ' ' . str('proposal')->plural($sent) . ' sent to the DRCP.');
    }

    /** @return Collection<int, array{application:SeedApplication, means:?array, passes:bool}> */
    public static function rank(Collection $applications): Collection
    {
        return $applications
            ->map(fn (SeedApplication $a) => ['application' => $a, 'means' => $m = $a->meanScores(),
                'passes' => $m !== null && SeedGrant::meetsThreshold($m['total'], $m['science'])])
            ->sort(function ($x, $y) {
                if (($x['means'] === null) !== ($y['means'] === null)) {
                    return $x['means'] === null ? 1 : -1;
                }
                foreach (array_merge(['total'], SeedGrant::TIE_BREAK) as $key) {
                    $cmp = ($y['means'][$key] ?? 0) <=> ($x['means'][$key] ?? 0);
                    if ($cmp !== 0) {
                        return $cmp;
                    }
                }

                return 0;
            })
            ->values();
    }
}
