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

    // OpenRouter (OpenAI-compatible gateway to many models, incl. Claude). The
    // provider is auto-detected from the key prefix (`sk-or-…`), so the key can
    // live in either ANTHROPIC_API_KEY or OPENROUTER_API_KEY and still work.
    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        // Current, low-cost model good for the help bot + plan starter; override
        // with AI_OPENROUTER_MODEL for a different one (see openrouter.ai/models).
        'model' => env('AI_OPENROUTER_MODEL', 'anthropic/claude-haiku-4.5'),
    ],

    // Balanced model for structured generation/extraction at consumer volume.
    'model' => env('AI_MODEL', 'claude-sonnet-4-6'),

    // Hard ceiling per response. Generous enough for ~30 tool-emitted items.
    'max_tokens' => (int) env('AI_MAX_TOKENS', 8000),

    // Request timeout (seconds) for the upstream call. Kept comfortably UNDER the
    // hosting platform's HTTP request limit (commonly ~30s) so a slow upstream
    // aborts here — yielding a graceful "service is busy" message — instead of
    // the platform killing the request and returning a hard 502/504 HTML page.
    'timeout' => (int) env('AI_TIMEOUT', 22),

];
