<?php

/**
 * Brand surface defaults. These are overridable at runtime from the admin
 * settings panel (see SettingsServiceProvider) so operators can re-skin the
 * product without editing code or .env.
 */
return [
    'primary' => env('BRAND_PRIMARY', '#9a7b4f'), // brushed gold
    'logo' => env('BRAND_LOGO', null),
    'tagline' => env('BRAND_TAGLINE', 'A wedding, composed.'),
];
