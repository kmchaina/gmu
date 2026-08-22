<?php

namespace App\Services\AI;

use App\Models\Award;
use App\Models\FundingCall;
use App\Models\Report;
use App\Models\User;

class GmuAiService
{
    public function __construct(
        private readonly LlmInterface         $llm,
        private readonly GrantMatchingEngine  $matcher,
        private readonly ComplianceRiskEngine $riskEngine,
    ) {}

    // ── Grant Matching ───────────────────────────────────────────────────────

    public function matchResearchersToCall(FundingCall $call): array
    {
        $matches = $this->matcher->topMatches($call);

        // If LLM available, enrich the top 3 with a natural language explanation
        if ($this->llm->isAvailable() && count($matches) > 0) {
            foreach (array_slice($matches, 0, 3) as $i => &$match) {
                $reasons = collect($match['reasons'])->pluck('detail')->implode('; ');
                $match['ai_summary'] = $this->llm->complete(
                    'You are a grants management advisor at a medical research institute. Be concise and professional.',
                    "Explain in 2 sentences why {$match['researcher']} (score: {$match['score']}/100) is a good match for this grant: \"{$call->title}\". Key factors: {$reasons}."
                );
            }
            unset($match);
        }

        return $matches;
    }

    // ── Compliance Risk Analysis ─────────────────────────────────────────────

    public function analyseAwardRisk(Award $award): array
    {
        $flags       = $this->riskEngine->analyse($award);
        $overallRisk = $this->riskEngine->overallRisk($flags);
        $aiNarrative = '';

        if ($this->llm->isAvailable() && !empty($flags)) {
            $flagSummary = collect($flags)
                ->map(fn($f) => "[{$f['level']}] {$f['category']}: {$f['message']}")
                ->implode("\n");

            $aiNarrative = $this->llm->complete(
                'You are a grants compliance officer. Write concise, action-oriented risk summaries.',
                "Summarise the compliance status of award \"{$award->title}\" in 3 sentences and recommend the single most urgent action. Flags:\n{$flagSummary}",
                300
            );
        }

        return [
            'flags'        => $flags,
            'overall_risk' => $overallRisk,
            'ai_narrative' => $aiNarrative,
            'llm_active'   => $this->llm->isAvailable(),
            'provider'     => $this->llm->providerName(),
        ];
    }

    // ── Report Quality Check ─────────────────────────────────────────────────

    public function checkReportQuality(Report $report, string $reportText): array
    {
        // Rule-based checks always run
        $issues = [];

        if (strlen($reportText) < 300) {
            $issues[] = ['level' => 'high', 'message' => 'Report is very short (under 300 characters). Most funders expect detailed narrative.'];
        }

        if (!preg_match('/\d/', $reportText)) {
            $issues[] = ['level' => 'medium', 'message' => 'No numbers detected. Progress reports should include quantitative data.'];
        }

        $requiredKeywords = ['objective', 'result', 'challenge', 'next'];
        $missing = [];
        foreach ($requiredKeywords as $kw) {
            if (!str_contains(strtolower($reportText), $kw)) {
                $missing[] = $kw;
            }
        }
        if ($missing) {
            $issues[] = ['level' => 'low', 'message' => 'Consider covering: ' . implode(', ', $missing) . '.'];
        }

        $aiSuggestions = '';
        if ($this->llm->isAvailable() && strlen($reportText) >= 100) {
            $aiSuggestions = $this->llm->complete(
                'You are a scientific writing advisor reviewing grant progress reports. Be constructive and specific.',
                "Review this grant progress report excerpt and provide 3 specific improvement suggestions:\n\n{$reportText}",
                500
            );
        }

        return [
            'rule_issues'    => $issues,
            'ai_suggestions' => $aiSuggestions,
            'llm_active'     => $this->llm->isAvailable(),
        ];
    }

    // ── Proposal Section Drafting (LLM only) ────────────────────────────────

    public function draftProposalSection(string $sectionType, string $context): string
    {
        if (!$this->llm->isAvailable()) {
            return '';
        }

        $prompts = [
            'abstract'    => "Draft a concise scientific abstract (250 words) for a research grant proposal based on this context: {$context}",
            'background'  => "Write a compelling background and rationale section (400 words) for a grant proposal: {$context}",
            'objectives'  => "Write 3-5 clear, measurable SMART objectives for a research grant proposal: {$context}",
            'methodology' => "Draft a methodology overview (350 words) for a research proposal: {$context}",
            'impact'      => "Write an expected impact and dissemination section (300 words) for a grant proposal: {$context}",
        ];

        $prompt = $prompts[$sectionType] ?? "Help draft a {$sectionType} section for a grant proposal: {$context}";

        return $this->llm->complete(
            'You are an expert grant writer at a leading medical research institute in East Africa. Write professionally and concisely.',
            $prompt,
            800
        );
    }

    public function llmAvailable(): bool
    {
        return $this->llm->isAvailable();
    }

    public function providerName(): string
    {
        return $this->llm->providerName();
    }
}
