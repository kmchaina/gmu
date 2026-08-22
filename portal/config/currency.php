<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reporting currency
    |--------------------------------------------------------------------------
    |
    | Awards and budgets are recorded in the currency of the donor contract.
    | Portfolio figures — total funding, funding by donor, budget utilisation —
    | are meaningless unless they are converted to one currency first
    | (audit finding M3). Everything aggregated is expressed in this one.
    |
    */

    'base' => env('GMU_BASE_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Exchange rates
    |--------------------------------------------------------------------------
    |
    | Units of the base currency for one unit of the listed currency. Maintained
    | by the Finance Officer — there is no live feed, deliberately: a grant is
    | reported at the rate agreed with the donor, not today's market rate.
    |
    | Each award also stores the rate that was applied when it was recorded, so
    | changing a rate here does not silently rewrite historical figures.
    |
    */

    'rates' => [
        'USD' => 1.0,
        'EUR' => 1.09,
        'GBP' => 1.27,
        'TZS' => 0.00037,
        'KES' => 0.0077,
        'CHF' => 1.13,
        'JPY' => 0.0067,
        'ZAR' => 0.055,
    ],

];
