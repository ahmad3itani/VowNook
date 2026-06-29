<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social profiles (schema.org sameAs)
    |--------------------------------------------------------------------------
    |
    | Public profile URLs added to the Organization's `sameAs` — this is how
    | search engines and AI assistants tie the brand to its presence elsewhere
    | and trust it as a real entity. Set the env vars once the profiles exist;
    | empties are filtered out and the property is omitted when none are set.
    |
    */

    'socials' => [
        env('SOCIAL_INSTAGRAM'),  // https://instagram.com/vownook
        env('SOCIAL_PINTEREST'),  // https://pinterest.com/vownook
        env('SOCIAL_FACEBOOK'),   // https://facebook.com/vownook
        env('SOCIAL_TIKTOK'),     // https://tiktok.com/@vownook
        env('SOCIAL_LINKEDIN'),   // https://linkedin.com/company/vownook
        env('SOCIAL_X'),          // https://x.com/vownook
    ],

];
