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
        // Hub Data Reporting API bearer token (secret) — reserved for a future
        // live earnings pull; their endpoint isn't publicly documented yet.
        'api_token' => env('STAY22_API_TOKEN'),
        'dashboard_url' => env('STAY22_DASHBOARD_URL', 'https://hub.stay22.com'),
    ],

    /*
    | GetYourGuide — experiences/activities affiliate. We deep-link to a search
    | for each AI-suggested experience; the partner id (optional) tracks
    | commission. Without it, links still work as a plain search.
    */
    'getyourguide' => [
        'partner_id' => env('GETYOURGUIDE_PARTNER_ID'),
        'search_base' => env('GETYOURGUIDE_SEARCH_BASE', 'https://www.getyourguide.com/s/'),
    ],

    /*
    | Travelpayouts (Aviasales) — flight-search affiliate. The "marker" is the
    | partner id appended to the search link; commission is tracked against it.
    | Inert until the marker is set. Guests are sent to a flight search to the
    | couple's nearest airport for the wedding weekend.
    */
    'travelpayouts' => [
        'marker' => env('TRAVELPAYOUTS_MARKER'),
        'aviasales_base' => env('TRAVELPAYOUTS_AVIASALES_BASE', 'https://search.aviasales.com/flights/'),
        'locale' => env('TRAVELPAYOUTS_LOCALE', 'en'),
        // API token (secret, from Profile → API token — NOT the public marker).
        // Powers the live balance pull + the honeymoon-concierge live prices.
        'api_token' => env('TRAVELPAYOUTS_API_TOKEN'),
        'api_base' => env('TRAVELPAYOUTS_API_BASE', 'https://api.travelpayouts.com'),
        'hotellook_base' => env('TRAVELPAYOUTS_HOTELLOOK_BASE', 'https://engine.hotellook.com'),
        'dashboard_url' => env('TRAVELPAYOUTS_DASHBOARD_URL', 'https://app.travelpayouts.com'),
    ],

];
