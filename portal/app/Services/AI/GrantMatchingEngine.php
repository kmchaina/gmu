<?php

namespace App\Services\AI;

use App\Models\FundingCall;
use App\Models\ResearcherProfile;
use App\Models\User;

class GrantMatchingEngine
{
    /**
     * Score how well a researcher matches a funding call.
     * Returns a score 0-100 and a breakdown of contributing factors.
     */
    public function scoreMatch(User $researcher, FundingCall $call): array
    {
        $profile = $researcher->researcherProfile;
        $score   = 0;
        $reasons = [];

        // 1. Expertise vs call theme (25 pts)
        if ($profile && $profile->expertise_areas) {
            $theme    = strtolower($call->theme ?? '');
            $desc     = strtolower($call->description ?? '');
            $haystack = $theme . ' ' . $desc;
            $matched  = [];
            foreach ($profile->expertise_areas as $area) {
                if (str_contains($haystack, strtolower($area))) {
                    $matched[] = $area;
                }
            }
            if ($matched) {
                $pts = min(25, count($matched) * 8);
                $score += $pts;
                $reasons[] = ['factor' => 'Expertise match', 'points' => $pts,
                    'detail' => 'Matching areas: ' . implode(', ', $matched)];
            }
        }

        // 2. Track record — past successful awards (20 pts)
        $wonGrants = $researcher->proposals()
            ->where('status', 'approved')
            ->whereHas('award')
            ->count();
        if ($wonGrants > 0) {
            $pts = min(20, $wonGrants * 5);
            $score += $pts;
            $reasons[] = ['factor' => 'Track record', 'points' => $pts,
                'detail' => "{$wonGrants} funded grant(s) as PI"];
        }

        // 3. Relevant qualifications (15 pts)
        if ($profile && $profile->qualifications) {
            $themeWords = preg_split('/\W+/', strtolower($call->theme ?? ''));
            foreach ($profile->qualifications as $qual) {
                $field = strtolower($qual['field'] ?? '');
                foreach ($themeWords as $word) {
                    if (strlen($word) > 3 && str_contains($field, $word)) {
                        $score += 15;
                        $reasons[] = ['factor' => 'Relevant qualification', 'points' => 15,
                            'detail' => "{$qual['degree']} in {$qual['field']}"];
                        break 2;
                    }
                }
            }
        }

        // 4. ORCID linked — signals active researcher (5 pts)
        if ($profile?->orcid_id) {
            $score += 5;
            $reasons[] = ['factor' => 'ORCID linked', 'points' => 5, 'detail' => $profile->orcid_id];
        }

        // 5. Publications (10 pts)
        $pubCount = count($profile?->publications ?? []);
        if ($pubCount > 0) {
            $pts = min(10, $pubCount * 2);
            $score += $pts;
            $reasons[] = ['factor' => 'Publications', 'points' => $pts,
                'detail' => "{$pubCount} publication(s) on record"];
        }

        // 6. No active FCOI conflicts (5 pts bonus)
        $hasUnresolvedFcoi = $researcher->fcoiDisclosures()
            ->where('has_conflict', true)
            ->whereNotIn('status', ['cleared'])
            ->exists();
        if (!$hasUnresolvedFcoi) {
            $score += 5;
            $reasons[] = ['factor' => 'No unresolved FCOI', 'points' => 5, 'detail' => 'No conflict of interest flags'];
        }

        // 7. Budget fit — researcher's historical awards vs call budget (20 pts)
        if ($call->estimated_budget > 0) {
            $avgPastBudget = \App\Models\Award::whereHas('proposal',
                fn($q) => $q->where('lead_pi_id', $researcher->id)
            )->avg('total_budget');

            if ($avgPastBudget > 0) {
                $ratio = min($avgPastBudget, $call->estimated_budget) /
                         max($avgPastBudget, $call->estimated_budget);
                $pts   = (int) round($ratio * 20);
                $score += $pts;
                $reasons[] = ['factor' => 'Budget fit', 'points' => $pts,
                    'detail' => 'Avg past award: ' . number_format($avgPastBudget, 0)];
            }
        }

        return [
            'score'       => min(100, $score),
            'grade'       => $this->grade(min(100, $score)),
            'reasons'     => $reasons,
            'researcher'  => $researcher->name,
            'call'        => $call->title,
        ];
    }

    /**
     * Return top-matching researchers for a given call.
     */
    public function topMatches(FundingCall $call, int $limit = 10): array
    {
        $researchers = User::whereHas('role', fn($q) => $q->whereIn('name', ['pi', 'research_scientist']))
            ->where('is_active', true)
            ->with('researcherProfile', 'proposals.award', 'fcoiDisclosures')
            ->get();

        $scored = $researchers->map(fn($r) => $this->scoreMatch($r, $call))
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();

        return $scored;
    }

    private function grade(int $score): string
    {
        return match(true) {
            $score >= 75 => 'Excellent',
            $score >= 55 => 'Good',
            $score >= 35 => 'Fair',
            default      => 'Weak',
        };
    }
}
