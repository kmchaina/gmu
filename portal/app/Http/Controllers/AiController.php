<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\FundingCall;
use App\Models\Report;
use App\Services\AI\GmuAiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(private readonly GmuAiService $ai) {}

    // ── Grant Matching ───────────────────────────────────────────────────────

    public function matchingIndex()
    {
        $calls = FundingCall::where('status', 'open')->orderBy('deadline')->get();
        return view('ai.matching', compact('calls'));
    }

    public function matchCall(FundingCall $call)
    {
        $matches = $this->ai->matchResearchersToCall($call);
        return view('ai.matching-results', compact('call', 'matches'));
    }

    // ── Risk Analysis ────────────────────────────────────────────────────────

    public function riskIndex()
    {
        $awards = Award::where('status', 'active')->orderBy('title')->get();
        return view('ai.risk', compact('awards'));
    }

    public function riskAnalysis(Award $award)
    {
        $result = $this->ai->analyseAwardRisk($award);
        return view('ai.risk-result', compact('award', 'result'));
    }

    // ── Report Quality ───────────────────────────────────────────────────────

    public function reportQuality(Request $request, Report $report)
    {
        $reportText = $request->validate(['text' => 'required|string|min:50'])['text'];
        $result     = $this->ai->checkReportQuality($report, $reportText);
        return response()->json($result);
    }

    // ── Proposal Section Drafting ────────────────────────────────────────────

    public function draftSection(Request $request)
    {
        abort_unless($this->ai->llmAvailable(), 503, 'AI provider not configured.');

        $data = $request->validate([
            'section'  => 'required|in:abstract,background,objectives,methodology,impact',
            'context'  => 'required|string|min:30|max:2000',
        ]);

        $draft = $this->ai->draftProposalSection($data['section'], $data['context']);
        return response()->json(['draft' => $draft]);
    }

    // ── AI Assistant Hub ─────────────────────────────────────────────────────

    public function hub()
    {
        return view('ai.hub', [
            'llmActive'    => $this->ai->llmAvailable(),
            'providerName' => $this->ai->providerName(),
        ]);
    }
}
