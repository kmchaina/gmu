<?php

namespace App\Services\AI;

use App\Models\Award;
use App\Support\Days;

class ComplianceRiskEngine
{
    /**
     * Analyse an award and return risk flags.
     * Each flag: ['level' => 'high|medium|low', 'category' => '...', 'message' => '...']
     */
    public function analyse(Award $award): array
    {
        $award->load(['reports', 'complianceCalendar', 'ethicsRenewals', 'budgets']);
        $flags = [];

        // ── Reports ──────────────────────────────────────────────────────────
        $overdueReports = $award->reports
            ->where('status', 'pending')
            ->filter(fn($r) => Days::isPast($r->due_date));

        foreach ($overdueReports as $r) {
            $days = Days::overdueBy($r->due_date);
            $flags[] = [
                'level'    => $days > 30 ? 'high' : 'medium',
                'category' => 'Reporting',
                'message'  => ucfirst($r->report_type) . " report \"{$r->title}\" is {$days} day(s) overdue.",
            ];
        }

        $soonReports = $award->reports
            ->where('status', 'pending')
            ->filter(fn($r) => Days::until($r->due_date) >= 0 && Days::until($r->due_date) <= 14);

        foreach ($soonReports as $r) {
            $days = Days::until($r->due_date);
            $flags[] = [
                'level'    => 'low',
                'category' => 'Reporting',
                'message'  => ucfirst($r->report_type) . " report \"{$r->title}\" is due {$days} day(s) from now.",
            ];
        }

        // ── Compliance Calendar ───────────────────────────────────────────────
        $overdueCompliance = $award->complianceCalendar
            ->whereIn('status', ['upcoming', 'overdue'])
            ->filter(fn($item) => Days::isPast($item->due_date));

        foreach ($overdueCompliance as $item) {
            $days = Days::overdueBy($item->due_date);
            $flags[] = [
                'level'    => $days > 14 ? 'high' : 'medium',
                'category' => 'Compliance',
                'message'  => "Compliance item \"{$item->title}\" ({$item->deliverable_type}) is {$days} day(s) overdue.",
            ];
        }

        // ── Ethics Renewals ──────────────────────────────────────────────────
        foreach ($award->ethicsRenewals as $ethics) {
            if ($ethics->renewal_status === 'renewed') {
                continue;
            }

            $daysLeft = Days::until($ethics->expiry_date);

            if ($daysLeft < 0) {
                $flags[] = [
                    'level'    => 'high',
                    'category' => 'Ethics',
                    'message'  => "Protocol {$ethics->ethics_number} expired " . abs($daysLeft) . " day(s) ago.",
                ];
            } elseif ($daysLeft <= 30) {
                $flags[] = [
                    'level'    => 'high',
                    'category' => 'Ethics',
                    'message'  => "Protocol {$ethics->ethics_number} expires in {$daysLeft} day(s) — renew urgently.",
                ];
            } elseif ($daysLeft <= 60) {
                $flags[] = [
                    'level'    => 'medium',
                    'category' => 'Ethics',
                    'message'  => "Protocol {$ethics->ethics_number} expires in {$daysLeft} day(s).",
                ];
            }
        }

        // ── Budget ───────────────────────────────────────────────────────────
        $allocated = $award->budgets->sum('allocated_amount');
        $spent     = $award->budgets->sum('spent_amount');

        if ($allocated > 0) {
            $burnPct = ($spent / $allocated) * 100;

            if ($burnPct > 110) {
                $flags[] = [
                    'level'    => 'high',
                    'category' => 'Budget',
                    'message'  => 'Budget over-run: spent ' . round($burnPct, 1) . '% of allocated funds.',
                ];
            } elseif ($burnPct > 90) {
                $flags[] = [
                    'level'    => 'medium',
                    'category' => 'Budget',
                    'message'  => 'Budget at ' . round($burnPct, 1) . '% — approaching limit.',
                ];
            }

            // Under-utilisation near award end
            $daysToEnd = Days::until($award->end_date);
            if ($award->end_date && $daysToEnd >= 0 && $daysToEnd <= 60 && $burnPct < 60) {
                $flags[] = [
                    'level'    => 'medium',
                    'category' => 'Budget',
                    'message'  => 'Only ' . round($burnPct, 1) . '% spent with less than 60 days remaining — risk of donor clawback.',
                ];
            }
        }

        // ── Award end approaching ─────────────────────────────────────────────
        if ($award->end_date) {
            $daysToEnd = Days::until($award->end_date);
            if ($daysToEnd <= 30 && $daysToEnd >= 0) {
                $flags[] = [
                    'level'    => 'high',
                    'category' => 'Closeout',
                    'message'  => "Award ends in {$daysToEnd} day(s) — initiate closeout procedures.",
                ];
            }
        }

        // Sort: high → medium → low
        usort($flags, fn($a, $b) => ['high'=>0,'medium'=>1,'low'=>2][$a['level']]
                                  - ['high'=>0,'medium'=>1,'low'=>2][$b['level']]);

        return $flags;
    }

    public function overallRisk(array $flags): string
    {
        $levels = array_column($flags, 'level');
        if (in_array('high', $levels))   return 'high';
        if (in_array('medium', $levels)) return 'medium';
        if (!empty($levels))             return 'low';
        return 'none';
    }
}
