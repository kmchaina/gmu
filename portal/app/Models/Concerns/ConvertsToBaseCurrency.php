<?php

namespace App\Models\Concerns;

use App\Support\Money;

/**
 * Keeps the reporting-currency equivalents in step with the contract amounts
 * (audit finding M3).
 *
 * A model using this declares which amount columns map to which base columns:
 *
 *     protected array $baseCurrencyMap = ['total_budget' => 'base_total_budget'];
 *
 * The rate is captured on the record when the amounts are written, so a later
 * change to the configured rates never rewrites historical figures.
 */
trait ConvertsToBaseCurrency
{
    public static function bootConvertsToBaseCurrency(): void
    {
        static::saving(function ($model) {
            $model->syncBaseCurrencyAmounts();
        });
    }

    public function syncBaseCurrencyAmounts(): void
    {
        $currencyChanged = $this->isDirty('currency');

        if ($currencyChanged || ! $this->exists || ! $this->exchange_rate) {
            $this->exchange_rate = Money::rate($this->currency);
        }

        foreach ($this->baseCurrencyMap as $source => $target) {
            if (! $currencyChanged && ! $this->isDirty($source) && $this->{$target} !== null) {
                continue;
            }

            $amount = $this->{$source};

            $this->{$target} = $amount === null
                ? null
                : round((float) $amount * (float) $this->exchange_rate, 2);
        }
    }
}
