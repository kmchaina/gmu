<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Whole-day arithmetic for deadlines (audit finding M5).
 *
 * Carbon 3 returns a float from diffInDays(), and date columns cast to
 * midnight while now() carries a time, so every difference came out
 * fractional: the risk report read "12.250000298727 day(s) overdue" and the
 * award page showed "Days remaining: 231.45833". Comparing calendar dates is
 * what the business actually means, so both sides are taken at start of day.
 */
final class Days
{
    /**
     * Whole days from today until $date. Negative once the date has passed.
     */
    public static function until(CarbonInterface|string|null $date): int
    {
        if ($date === null) {
            return 0;
        }

        $target = Carbon::parse($date)->startOfDay();

        return (int) Carbon::now()->startOfDay()->diffInDays($target, false);
    }

    /**
     * Whole days a date is past. Zero if it is today or still ahead.
     */
    public static function overdueBy(CarbonInterface|string|null $date): int
    {
        return max(0, -self::until($date));
    }

    public static function isPast(CarbonInterface|string|null $date): bool
    {
        return $date !== null && self::until($date) < 0;
    }

    /**
     * "in 12 days" / "3 days ago" / "today" — for message text.
     */
    public static function describe(CarbonInterface|string|null $date): string
    {
        $days = self::until($date);

        return match (true) {
            $days === 0 => 'today',
            $days === 1 => 'tomorrow',
            $days === -1 => 'yesterday',
            $days > 0 => "in {$days} days",
            default => abs($days) . ' days ago',
        };
    }
}
