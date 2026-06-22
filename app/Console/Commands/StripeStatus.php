<?php

namespace App\Console\Commands;

use App\Support\Payments\StripeService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Read-only health check for the Stripe integration. Reports which keys are set,
 * whether the secret authenticates (test vs live mode), Connect readiness, and
 * whether a webhook endpoint targets this app with the events the handler needs
 * — without ever printing a secret value.
 */
class StripeStatus extends Command
{
    protected $signature = 'stripe:status';

    protected $description = 'Diagnose the Stripe payments configuration (never prints secrets)';

    /** Events StripeService::handleEvent() acts on — the webhook must send these. */
    private const REQUIRED_EVENTS = [
        'checkout.session.completed',
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'account.updated',
        'charge.refunded',
    ];

    public function handle(StripeService $stripe): int
    {
        $secret = (string) config('services.stripe.secret');
        $key = (string) config('services.stripe.key');
        $webhookSecret = (string) config('services.stripe.webhook_secret');

        $mode = str_starts_with($secret, 'sk_live_') ? 'LIVE'
            : (str_starts_with($secret, 'sk_test_') ? 'TEST' : 'unknown');

        $this->info('Stripe keys');
        $this->row('STRIPE_SECRET', $secret !== '' ? "set ({$mode} mode)" : 'MISSING', $secret !== '');
        $this->row('STRIPE_KEY (publishable)', $key !== '' ? 'set' : 'missing', $key !== '');
        $this->row('STRIPE_WEBHOOK_SECRET', $webhookSecret !== '' ? 'set' : 'MISSING', $webhookSecret !== '');

        if (! $stripe->isConfigured()) {
            $this->newLine();
            $this->warn('No secret key set — payments are inert (buttons hidden, webhook no-ops).');
            $this->line('Set STRIPE_SECRET (+ STRIPE_KEY, STRIPE_WEBHOOK_SECRET) to switch payments on.');

            return self::SUCCESS;
        }

        // 1. Does the secret actually authenticate with Stripe?
        $this->newLine();
        $this->info('Live API check');
        try {
            $account = $stripe->client()->accounts->retrieve();
            $this->row('Authenticates with Stripe', "yes — {$account->id}", true);
            $this->line('    country='.($account->country ?? '?').'  charges_enabled='.(($account->charges_enabled ?? false) ? 'yes' : 'no'));
        } catch (Throwable $e) {
            $this->row('Authenticates with Stripe', 'NO — key rejected', false);
            $this->line('    '.$e->getMessage());

            return self::FAILURE;
        }

        // 2. Is Connect enabled (needed for vendor payouts)?
        try {
            $stripe->client()->accounts->all(['limit' => 1]);
            $this->row('Connect enabled', 'yes', true);
        } catch (Throwable $e) {
            $this->row('Connect enabled', 'NO — enable Connect in the Dashboard', false);
        }

        // 3. Is a webhook endpoint registered for this app with all required events?
        $this->newLine();
        $this->info('Webhook');
        $target = route('stripe.webhook');
        $this->line("    expected URL: {$target}");

        try {
            $endpoints = $stripe->client()->webhookEndpoints->all(['limit' => 100]);
            $match = collect($endpoints->data)->firstWhere('url', $target);

            if ($match === null) {
                $this->row('Endpoint registered', 'NO — add it in the Dashboard (or check APP_URL)', false);
            } else {
                $this->row('Endpoint registered', "yes — {$match->status}", $match->status === 'enabled');

                $events = $match->enabled_events ?? [];
                $hasAll = in_array('*', $events, true) || empty(array_diff(self::REQUIRED_EVENTS, $events));
                $this->row('Subscribed to required events', $hasAll ? 'yes' : 'missing some', $hasAll);

                if (! $hasAll) {
                    $this->line('    missing: '.implode(', ', array_diff(self::REQUIRED_EVENTS, $events)));
                }
            }
        } catch (Throwable $e) {
            $this->row('Endpoint check', 'could not list endpoints', false);
            $this->line('    '.$e->getMessage());
        }

        $this->newLine();
        $this->info('Done — no secret values were displayed.');

        return self::SUCCESS;
    }

    private function row(string $label, string $value, bool $ok): void
    {
        $this->line(sprintf('  %s %-30s %s', $ok ? '<fg=green>OK</>' : '<fg=red>--</>', $label, $value));
    }
}
