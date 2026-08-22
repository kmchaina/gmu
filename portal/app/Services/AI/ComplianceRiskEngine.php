<?php

namespace App\Services\AI;

use App\Models\Award;

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
            ->where('due_date', '<', now());

        foreach ($overdueReports as $r) {
            $days = $r->due_date->diffInDays(now());
            $flags[] = [
                'level'    => $days > 30 ? 'high' : 'medium',
                'category' => 'Reporting',
                'message'  => "{$r->report_type} report \"{$r->title}\" is {$days} day(s) overdue.",
            ];
        }

        $soonReports = $award->reports
            ->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays(14)]);

        foreach ($soonReports as $r) {
            $days = now()->diffInDays($r->due_date);
            $flags[] = [
                'level'    => 'low',
                'category' => 'Reporting',
                'message'  => "{$r->report_type} report due in {$days} day(s).",
            ];
        }

        // ── Compliance Calendar ───────────────────────────────────────────────
        $overdueCompliance = $award->complianceCalendar
            ->where('status', 'upcoming')
            ->where('due_date', '<', now());

        foreach ($overdueCompliance as $item) {
            $days = $item->due_date->diffInDays(now());
            $flags[] = [
                'level'    => $days > 14 ? 'high' : 'medium',
                'category' => 'Compliance',
                'message'  => "Compliance item \"{$item->title}\" ({$item->deliverable_type}) is {$days} day(s) overdue.",
            ];
        }

        // ── Ethics Renewals ──────────────────────────────────────────────────
        foreach ($award->ethicsRenewals as $ethics) {
            if (in_array($ethics->renewal_status, ['renewed'])) continue;

            $daysLeft = now()->diffInDays($ethics->expiry_date, false);

            if ($daysLeft < 0) {
                $flags[] = [
                    'level'    => 'high',
                    'category' => 'Ethics',
                    'message'  => "Protocol {$ethics->ethics_number} expired " . abs((int)$daysLeft) . " day(s) ago.",
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
            if ($award->end_date && now()->diffInDays($award->end_date, false) <= 60 && $burnPct < 60) {
                $flags[] = [
                    'level'    => 'medium',
                    'category' => 'Budget',
                    'message'  => 'Only ' . round($burnPct, 1) . '% spent with less than 60 days remaining — risk of donor clawback.',
                ];
            }
        }

        // ── Award end approaching ─────────────────────────────────────────────
        if ($award->end_date) {
            $daysToEnd = now()->diffInDays($award->end_date, false);
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
