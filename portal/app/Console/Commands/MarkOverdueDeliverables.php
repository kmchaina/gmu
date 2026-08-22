<?php

namespace App\Console\Commands;

use App\Models\ComplianceCalendar;
use App\Services\Notifier;
use App\Support\Days;
use Illuminate\Console\Command;

/**
 * Audit finding M6 — the compliance calendar defines an "overdue" status that
 * nothing ever set, so a missed deliverable sat in the calendar looking
 * upcoming forever. This runs each morning and tells the GMU office what has
 * slipped.
 */
class MarkOverdueDeliverables extends Command
{
    protected $signature   = 'gmu:mark-overdue';
    protected $description = 'Flag compliance deliverables whose due date has passed.';

    public function handle(Notifier $notifier): int
    {
        $slipped = ComplianceCalendar::with('award')
            ->where('status', 'upcoming')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($slipped as $item) {
            $item->update(['status' => 'overdue']);

            $notifier->gmuOffice(
                'compliance_overdue',
                'Deliverable overdue: ' . $item->title,
                sprintf(
                    '"%s" for %s was due %s and is now %d day(s) overdue.',
                    $item->title,
                    $item->award?->title ?? 'an award',
                    $item->due_date?->format('j M Y'),
                    Days::overdueBy($item->due_date)
                ),
                $item
            );
        }

        $this->info("Marked {$slipped->count()} deliverable(s) overdue.");

        return self::SUCCESS;
    }
}
