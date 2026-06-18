<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Analytics & Search Console
    |--------------------------------------------------------------------------
    |
    | All values are read from the environment and degrade gracefully when
    | unset. Tracking scripts (GA4, Clarity) load only in production with a
    | configured ID and only after the visitor consents (Google Consent Mode v2);
    | the search-engine verification meta tags render whenever their value is set.
    |
    */

    // Google Analytics 4 measurement ID, e.g. "G-XXXXXXXXXX".
    'ga_id' => env('GA_MEASUREMENT_ID'),

    // Microsoft Clarity project ID (free heatmaps + session recordings).
    'clarity_id' => env('MS_CLARITY_ID'),

    // Google Search Console "HTML tag" verification token.
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),

    // Bing Webmaster Tools verification token.
    'bing_site_verification' => env('BING_SITE_VERIFICATION'),

    // Force-enable tracking outside production (for staging tests). Default: off.
    'force' => env('ANALYTICS_FORCE', false),
];
