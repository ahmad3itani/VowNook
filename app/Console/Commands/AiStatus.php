<?php

namespace App\Console\Commands;

use App\Support\Ai\AiService;
use Illuminate\Console\Command;

/**
 * Read-only health check for the AI integration. Reports whether AI is enabled,
 * which provider/model is in use, and makes a tiny live call to surface the
 * exact reason a request fails (bad model, no credits, invalid key) — without
 * ever printing the API key.
 */
class AiStatus extends Command
{
    protected $signature = 'ai:status';

    protected $description = 'Diagnose the AI configuration with a live test call (never prints the key)';

    public function handle(AiService $ai): int
    {
        $this->info('AI configuration');
        $this->line('  enabled: '.(config('ai.enabled') ? 'yes' : 'no'));

        if (! $ai->isConfigured()) {
            $this->newLine();
            $this->warn('No API key set — AI features are off (the bot hides, planner shows an upsell).');
            $this->line('Set ANTHROPIC_API_KEY (sk-ant-…) or OPENROUTER_API_KEY (sk-or-…).');

            return self::SUCCESS;
        }

        $this->line('  provider: '.$ai->provider().'  (auto-detected from key prefix)');
        $this->line('  model:    '.$ai->model());

        $this->newLine();
        $this->info('Live test call…');

        $result = $ai->ping();

        if ($result['ok']) {
            $this->line('  <fg=green>OK</> the provider accepted the request — AI is working.');

            return self::SUCCESS;
        }

        $this->line('  <fg=red>FAILED</> status='.($result['status'] ?? 'n/a'));
        $this->line('  reason: '.($result['error'] ?? 'unknown'));
        $this->newLine();

        // The most common, actionable causes.
        match (true) {
            $result['status'] === 401 => $this->warn('401 = the key was rejected. Re-check the API key value.'),
            $result['status'] === 402 => $this->warn('402 = no credit on the account. Add credit (OpenRouter) or billing (Anthropic).'),
            in_array($result['status'], [400, 404], true) => $this->warn('400/404 often = the model slug is not available to this account. Set AI_OPENROUTER_MODEL to a model you can use.'),
            default => $this->warn('See the reason above. Adjust the key, model, or account as needed.'),
        };

        return self::FAILURE;
    }
}
