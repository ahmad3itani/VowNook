<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform commission
    |--------------------------------------------------------------------------
    |
    | Tiered take rate applied when a couple accepts a vendor's offer:
    | `rate` on the portion up to `threshold_cents`, `rate_above` on the
    | remainder, with the total fee capped at `cap_cents`. Tiering keeps the
    | fee tolerable on big-ticket bookings (venues), which is where flat
    | commissions get evaded off-platform.
    |
    */

    'commission' => [
        'rate' => (float) env('MARKETPLACE_COMMISSION_RATE', 0.08),
        'rate_above' => (float) env('MARKETPLACE_COMMISSION_RATE_ABOVE', 0.05),
        'threshold_cents' => (int) env('MARKETPLACE_COMMISSION_THRESHOLD_CENTS', 500000),
        'cap_cents' => (int) env('MARKETPLACE_COMMISSION_CAP_CENTS', 100000),
    ],

];
