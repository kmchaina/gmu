<?php

namespace App\Support;

/**
 * Currency conversion for portfolio figures (audit finding M3).
 *
 * Awards, budget lines and calls each carry their own currency, but every
 * aggregate — total funding, funding by donor, budget utilisation — summed the
 * raw amounts as though a Tanzanian shilling and a US dollar were the same
 * thing. Records now store a base-currency amount alongside the contract
 * amount, and aggregates use that.
 */
final class Money
{
    public static function base(): string
    {
        return strtoupper((string) config('currency.base', 'USD'));
    }

    /** Units of the base currency for one unit of $currency. */
    public static function rate(?string $currency): float
    {
        $currency = strtoupper($currency ?: self::base());

        if ($currency === self::base()) {
            return 1.0;
        }

        return (float) (config('currency.rates')[$currency] ?? 0.0);
    }

    /** True when we hold no rate for the currency, so conversion would be a guess. */
    public static function isConvertible(?string $currency): bool
    {
        return self::rate($currency) > 0;
    }

    /**
     * Convert an amount to the base currency. Returns null when no rate is
     * configured — better a visible gap than a fabricated total.
     */
    public static function toBase(float|string|null $amount, ?string $currency): ?float
    {
        if ($amount === null) {
            return null;
        }

        $rate = self::rate($currency);

        return $rate > 0 ? round((float) $amount * $rate, 2) : null;
    }

    public static function format(float|string|null $amount, ?string $currency = null): string
    {
        return ($currency ?: self::base()) . ' ' . number_format((float) $amount, 2);
    }

    /** Currencies the system can convert, for form dropdowns. */
    public static function supported(): array
    {
        return array_keys(config('currency.rates', ['USD' => 1.0]));
    }
}
