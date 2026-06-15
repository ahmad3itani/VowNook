<?php

namespace App\Http\Controllers;

use App\Support\Payments\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;
use UnexpectedValueException;

/**
 * Receives Stripe webhooks — the source of truth for advancing booking state
 * after a hosted Checkout completes, and for syncing Connect onboarding status.
 * Public + CSRF-exempt; every event is signature-verified.
 */
class StripeWebhookController extends Controller
{
    public function __construct(protected StripeService $stripe) {}

    public function handle(Request $request): Response
    {
        // No keys configured — accept and ignore so Stripe doesn't retry forever.
        if (! $this->stripe->isConfigured()) {
            return response('', 200);
        }

        try {
            $event = $this->stripe->constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
            );
        } catch (UnexpectedValueException|\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        try {
            $this->stripe->handleEvent($event);
        } catch (Throwable $e) {
            report($e);

            // 500 tells Stripe to retry later.
            return response('Handler error', 500);
        }

        return response('', 200);
    }
}
