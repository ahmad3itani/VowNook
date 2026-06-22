<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Travel affiliates
    |--------------------------------------------------------------------------
    |
    | Powers the "Stays near your venue" block on a couple's wedding website:
    | an interactive map of hotels & short-stays guests can book, with the
    | commission tracked against our partner account.
    |
    | The Stay22 account id is a public embed parameter (it appears in the
    | widget URL on every site that uses it) — it is NOT a secret. Even so, it
    | lives only in the environment, and the whole block stays inert until it's
    | set, exactly like the Stripe and AI integrations.
    |
    */

    'stay22' => [
        'id' => env('STAY22_ID'),
        'embed_base' => env('STAY22_EMBED_BASE', 'https://www.stay22.com/embed/gm'),
        // Widget accent colour, hex without the leading '#'. Defaults to VowNook gold.
        'maincolor' => env('STAY22_MAINCOLOR', '8a651c'),
        // Tag for segmenting earnings in the Stay22 dashboard.
        'campaign' => env('STAY22_CAMPAIGN', 'vownook-website'),
    ],

];
