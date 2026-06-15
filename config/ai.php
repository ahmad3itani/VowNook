<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI assistance (Anthropic Claude)
    |--------------------------------------------------------------------------
    |
    | Powers the couple/planner "AI Plan Starter" — generating a starter
    | checklist, budget, and day-of timeline from the wedding's own details.
    |
    | The feature degrades gracefully: when no API key is present the AI
    | endpoints report "not configured" and the rest of the app is unaffected.
    | The key lives ONLY in the environment — never commit it.
    |
    */

    'enabled' => (bool) env('AI_ENABLED', true),

    'provider' => env('AI_PROVIDER', 'anthropic'),

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
    ],

    // Balanced model for structured generation/extraction at consumer volume.
    'model' => env('AI_MODEL', 'claude-sonnet-4-6'),

    // Hard ceiling per response. Generous enough for ~30 tool-emitted items.
    'max_tokens' => (int) env('AI_MAX_TOKENS', 8000),

    // Request timeout (seconds) for the upstream call.
    'timeout' => (int) env('AI_TIMEOUT', 60),

];
