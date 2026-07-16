<?php

namespace App\Support\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Mail\ShopOrderDelivery;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ShopOrder;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\PaymentDisputeOpened;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Wraps Stripe Connect for the marketplace: Express onboarding for vendors and
 * hosted Checkout for couples, using destination charges so the platform takes
 * its commission (application_fee) and the remainder is paid out to the vendor.
 *
 * Degrades gracefully — when no secret key is configured, isConfigured() is
 * false and callers skip Stripe entirely. The webhook source of truth advances
 * booking state; success URLs are cosmetic only.
 */
class StripeService
{
    /**
     * Per-charge statement descriptor suffix so card statements read
     * "{account prefix}* VOWNOOK" instead of the bare account descriptor —
     * the Stripe account is shared with another business, so the account-wide
     * descriptor can't simply be renamed.
     */
    private const DESCRIPTOR_SUFFIX = 'VOWNOOK';

    /**
     * A single reusable Stripe Coupon id backing the referred-side $20-off
     * Atelier discount. Native Stripe Coupons/Checkout `discounts` do the
     * math and display, so no custom discount ledger is needed here.
     */
    private const REFERRAL_COUPON_ID = 'vownook-referral-20off';

    /** $20.00 CAD, in cents. */
    private const REFERRAL_DISCOUNT_CENTS = 2000;

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }

    // ── Connect onboarding ──────────────────────────────────────────────────

    /** Ensure the vendor has a Stripe Express account; returns its id. */
    public function ensureAccount(VendorProfile $vendor): string
    {
        if (filled($vendor->stripe_account_id)) {
            return $vendor->stripe_account_id;
        }

        $account = $this->client()->accounts->create([
            'type' => 'express',
            'email' => $vendor->email,
            'business_profile' => ['name' => $vendor->business_name],
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'metadata' => ['vendor_profile_id' => $vendor->id],
        ]);

        $vendor->forceFill(['stripe_account_id' => $account->id])->save();

        return $account->id;
    }

    /** A one-time onboarding URL the vendor is redirected to. */
    public function onboardingLink(VendorProfile $vendor, string $returnUrl, string $refreshUrl): string
    {
        $accountId = $this->ensureAccount($vendor);

        $link = $this->client()->accountLinks->create([
            'account' => $accountId,
            'return_url' => $returnUrl,
            'refresh_url' => $refreshUrl,
            'type' => 'account_onboarding',
        ]);

        return $link->url;
    }

    /** Pull the latest onboarding state from Stripe onto the vendor profile. */
    public function syncAccountStatus(VendorProfile $vendor): void
    {
        if (blank($vendor->stripe_account_id)) {
            return;
        }

        $account = $this->client()->accounts->retrieve($vendor->stripe_account_id);

        $vendor->forceFill([
            'stripe_charges_enabled' => (bool) $account->charges_enabled,
            'stripe_details_submitted' => (bool) $account->details_submitted,
        ])->save();
    }

    // ── SaaS plan billing ────────────────────────────────────────────────────

    /** Ensure the user has a Stripe Customer; returns its id. */
    public function ensureCustomer(User $user): string
    {
        if (filled($user->stripe_customer_id)) {
            return $user->stripe_customer_id;
        }

        $customer = $this->client()->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => $user->id],
        ]);

        $user->forceFill(['stripe_customer_id' => $customer->id])->save();

        return $customer->id;
    }

    /**
     * Whether this user is entitled to the referred-side $20-off Atelier
     * discount: they were referred by someone, and haven't redeemed it yet.
     * Pure (no Stripe call) so it's cheap to use for display purposes too.
     */
    public function referralDiscountEligible(User $user): bool
    {
        return $user->referred_by !== null && $user->referral_discount_used_at === null;
    }

    /**
     * Idempotently ensure the shared referral discount Coupon exists in
     * Stripe. Cheap to call on every eligible checkout — only hits the
     * network to create it the very first time.
     */
    protected function ensureReferralCoupon(): void
    {
        try {
            $this->client()->coupons->retrieve(self::REFERRAL_COUPON_ID);
        } catch (\Throwable $e) {
            try {
                $this->client()->coupons->create([
                    'id' => self::REFERRAL_COUPON_ID,
                    'amount_off' => self::REFERRAL_DISCOUNT_CENTS,
                    'currency' => 'cad',
                    'duration' => 'once',
                    'name' => 'Referral — $20 off Atelier',
                ]);
            } catch (\Throwable $e) {
                // Most likely a concurrent request already created it between
                // our retrieve() and this create() — the coupon exists either
                // way, so don't crash this checkout over it. If creation
                // genuinely failed, the coupon reference below will surface
                // that clearly when the Checkout session itself is created.
            }
        }
    }

    /**
     * Hosted Checkout to upgrade a user's plan: a one-time payment for the
     * couple Atelier tier (priced per wedding) or an annual subscription for
     * Planner HQ. Returns the URL to redirect the user to.
     */
    public function planCheckout(User $user, string $tier, string $successUrl, string $cancelUrl): string
    {
        $config = config("plans.tiers.{$tier}");
        $isSubscription = $tier === 'planner';

        $params = [
            'mode' => $isSubscription ? 'subscription' : 'payment',
            'customer' => $this->ensureCustomer($user),
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            // The Stripe account is shared with another business, so the
            // recognisable name goes on per-charge suffixes, not account-wide.
            ...($isSubscription ? [] : [
                'payment_intent_data' => ['statement_descriptor_suffix' => self::DESCRIPTOR_SUFFIX],
            ]),
            'line_items' => [[
                'quantity' => 1,
                'price_data' => array_filter([
                    'currency' => 'cad',
                    'unit_amount' => ((int) $config['price']) * 100,
                    'product_data' => ['name' => 'VowNook — '.$config['name']],
                    'recurring' => $isSubscription ? ['interval' => 'year'] : null,
                ]),
            ]],
            'metadata' => ['user_id' => $user->id, 'plan_tier' => $tier],
        ];

        // Mirror metadata onto the subscription so subscription.* webhooks can
        // resolve the user even without the original Checkout session.
        if ($isSubscription) {
            $params['subscription_data'] = [
                'metadata' => ['user_id' => $user->id, 'plan_tier' => $tier],
            ];
        }

        // The referred-side discount only ever applies to the one-time
        // Atelier purchase, never the Planner HQ subscription, and only once
        // per referred user.
        if ($tier === 'premium' && $this->referralDiscountEligible($user)) {
            $this->ensureReferralCoupon();
            $params['discounts'] = [['coupon' => self::REFERRAL_COUPON_ID]];
        }

        return $this->client()->checkout->sessions->create($params)->url;
    }

    // ── Shop (digital stationery) ────────────────────────────────────────────

    /**
     * Hosted Checkout for a VowNook Shop order. Saves the session id on the
     * pending order (the webhook resolves it via metadata.shop_order_id) and
     * returns the URL to redirect the buyer to.
     */
    public function shopCheckout(ShopOrder $order, string $successUrl, string $cancelUrl): string
    {
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'billing_address_collection' => 'auto',
            'payment_intent_data' => ['statement_descriptor_suffix' => self::DESCRIPTOR_SUFFIX],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $order->currency,
                    'unit_amount' => $order->amount_cents,
                    'product_data' => ['name' => 'VowNook · '.$order->product_name],
                ],
            ]],
            'metadata' => [
                'shop_order_id' => $order->id,
                'product_name' => $order->product_name,
            ],
        ]);

        $order->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }

    // ── Checkout (marketplace bookings) ──────────────────────────────────────

    /** The amount (cents) due for a given payment stage on a booking. */
    public function amountFor(Booking $booking, PaymentType $type): int
    {
        // A separate deposit only exists when 0 < deposit < total; otherwise the
        // "deposit" stage collects the full amount and there is no balance.
        $hasSplit = $booking->deposit_cents > 0 && $booking->deposit_cents < $booking->total_cents;

        return match ($type) {
            PaymentType::Deposit => $hasSplit ? $booking->deposit_cents : $booking->total_cents,
            PaymentType::Balance => $hasSplit ? $booking->total_cents - $booking->deposit_cents : 0,
            PaymentType::Refund => 0,
        };
    }

    /**
     * The platform commission attributed to this stage. The deposit takes its
     * prorated share and the balance takes the exact remainder, so the two
     * always sum to booking.platform_fee_cents.
     */
    public function feeFor(Booking $booking, PaymentType $type): int
    {
        if ($booking->total_cents <= 0) {
            return 0;
        }

        $depositAmount = $this->amountFor($booking, PaymentType::Deposit);
        $depositFee = (int) round($booking->platform_fee_cents * $depositAmount / $booking->total_cents);

        return match ($type) {
            PaymentType::Deposit => $depositFee,
            PaymentType::Balance => max(0, $booking->platform_fee_cents - $depositFee),
            PaymentType::Refund => 0,
        };
    }

    /**
     * Create a hosted Checkout session for this stage, record a pending Payment,
     * and return the URL to redirect the couple to.
     */
    public function checkoutFor(Booking $booking, PaymentType $type, string $successUrl, string $cancelUrl): string
    {
        $session = $this->client()->checkout->sessions->create(
            $this->checkoutParams($booking, $type, $successUrl, $cancelUrl),
        );

        $this->recordPendingPayment($booking, $type, $session->id);

        return $session->url;
    }

    /**
     * The exact Checkout Session arguments — a destination charge so the
     * application_fee goes to the platform and the rest to the vendor's account.
     *
     * @return array<string,mixed>
     */
    public function checkoutParams(Booking $booking, PaymentType $type, string $successUrl, string $cancelUrl): array
    {
        $vendor = $booking->vendorProfile;

        return [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'cad',
                    'unit_amount' => $this->amountFor($booking, $type),
                    'product_data' => [
                        'name' => "{$vendor->business_name} — {$type->label()}",
                    ],
                ],
            ]],
            'payment_intent_data' => [
                'application_fee_amount' => $this->feeFor($booking, $type),
                'transfer_data' => ['destination' => $vendor->stripe_account_id],
                'statement_descriptor_suffix' => self::DESCRIPTOR_SUFFIX,
            ],
            'metadata' => [
                'booking_id' => $booking->id,
                'payment_type' => $type->value,
            ],
        ];
    }

    /** Persist the pending Payment that a Checkout session represents. */
    public function recordPendingPayment(Booking $booking, PaymentType $type, string $sessionId): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'type' => $type->value,
            'amount_cents' => $this->amountFor($booking, $type),
            'application_fee_cents' => $this->feeFor($booking, $type),
            'status' => PaymentStatus::Pending->value,
            'currency' => 'cad',
            'stripe_session_id' => $sessionId,
        ]);
    }

    // ── Refunds ─────────────────────────────────────────────────────────────

    /**
     * Issue a full refund for a booking's paid charges, from within the app.
     * `reverse_transfer` claws the money back out of the vendor's payout and
     * `refund_application_fee` returns the platform commission — so the refund is
     * funded by the original parties, not the platform's own balance. The
     * `charge.refunded` webhook then records it and cancels the booking. Returns
     * the total cents refunded.
     */
    public function refundBooking(Booking $booking): int
    {
        $payments = $booking->payments()
            ->where('status', PaymentStatus::Succeeded->value)
            ->whereIn('type', [PaymentType::Deposit->value, PaymentType::Balance->value])
            ->whereNotNull('stripe_payment_intent_id')
            ->get();

        $refunded = 0;

        foreach ($payments as $payment) {
            $this->client()->refunds->create([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'reverse_transfer' => true,
                'refund_application_fee' => true,
            ]);
            $refunded += (int) $payment->amount_cents;
        }

        return $refunded;
    }

    // ── Webhook ─────────────────────────────────────────────────────────────

    /** Verify the signature and parse the event (throws on a bad signature). */
    public function constructEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, (string) config('services.stripe.webhook_secret'));
    }

    /** Apply a verified webhook event to our records. */
    public function handleEvent(Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->onCheckoutCompleted($event->data->object),
            'customer.subscription.updated' => $this->onSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted' => $this->onSubscriptionEnded($event->data->object),
            'account.updated' => $this->onAccountUpdated($event->data->object),
            'charge.refunded' => $this->onChargeRefunded($event->data->object),
            'charge.dispute.created' => $this->onDisputeCreated($event->data->object),
            'charge.dispute.closed' => $this->onDisputeClosed($event->data->object),
            default => null,
        };
    }

    protected function onCheckoutCompleted($session): void
    {
        // Shop orders carry a shop_order_id in metadata.
        $shopOrderId = $session->metadata->shop_order_id ?? null;
        if ($shopOrderId !== null) {
            $this->onShopOrderPaid($session, (int) $shopOrderId);

            return;
        }

        // SaaS plan purchases carry a plan_tier in metadata; bookings don't.
        $tier = $session->metadata->plan_tier ?? null;
        if ($tier !== null) {
            $this->onPlanPurchased($session, $tier);

            return;
        }

        $payment = Payment::where('stripe_session_id', $session->id)->first();

        if ($payment === null || $payment->status === PaymentStatus::Succeeded) {
            return;
        }

        $payment->update([
            'status' => PaymentStatus::Succeeded->value,
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
        ]);

        $booking = $payment->booking;
        if ($booking === null) {
            return;
        }

        // Advance the booking and reflect what's been paid on the CRM row.
        $fullyPaid = $payment->type === PaymentType::Balance
            || ($payment->type === PaymentType::Deposit
                && $this->amountFor($booking, PaymentType::Balance) === 0);

        $booking->update([
            'status' => $fullyPaid ? BookingStatus::PaidInFull->value : BookingStatus::DepositPaid->value,
        ]);

        $paid = (int) $booking->payments()->where('status', PaymentStatus::Succeeded->value)
            ->whereIn('type', [PaymentType::Deposit->value, PaymentType::Balance->value])
            ->sum('amount_cents');

        $booking->vendor?->update(['paid_cents' => $paid]);
    }

    /**
     * A paid shop order: mark it fulfilled and email the buyer their signed
     * download link. Idempotent (Stripe retries webhooks), and the mail send is
     * wrapped so a mailer hiccup never 500s the webhook into a retry loop.
     */
    protected function onShopOrderPaid($session, int $orderId): void
    {
        $order = ShopOrder::find($orderId);

        if ($order === null || $order->isFulfilled()) {
            return;
        }

        $order->update([
            'status' => 'fulfilled',
            'email' => $session->customer_details->email ?? $order->email,
            'amount_cents' => $session->amount_total ?? $order->amount_cents,
            'fulfilled_at' => now(),
        ]);

        if (blank($order->email)) {
            Log::warning('Shop order fulfilled without a buyer email', ['shop_order_id' => $order->id]);

            return;
        }

        try {
            Mail::to($order->email)->send(new ShopOrderDelivery($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** A completed plan Checkout (one-time Atelier or the first HQ invoice). */
    protected function onPlanPurchased($session, string $tier): void
    {
        $userId = $session->metadata->user_id ?? null;
        $user = $userId ? User::find($userId) : null;

        if ($user === null) {
            return;
        }

        $user->forceFill([
            'plan' => $tier,
            'stripe_subscription_id' => $session->subscription ?? $user->stripe_subscription_id,
            // A real purchase supersedes any comp-code grant.
            'plan_comped_until' => null,
        ])->save();

        // Mark the referred-side discount used only on a completed, paid
        // checkout — the webhook is the source of truth, never the client-side
        // success redirect — so an abandoned session never burns the credit.
        if ($tier === 'premium' && $this->referralDiscountEligible($user)) {
            $user->forceFill(['referral_discount_used_at' => now()])->save();
        }
    }

    /** Keep the planner subscription's plan in sync with its Stripe status. */
    protected function onSubscriptionUpdated($subscription): void
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();

        if ($user === null) {
            return;
        }

        if (in_array($subscription->status, ['active', 'trialing'], true)) {
            $user->forceFill(['plan' => 'planner'])->save();
        } elseif (in_array($subscription->status, ['canceled', 'unpaid'], true)) {
            $user->forceFill(['plan' => config('plans.default'), 'stripe_subscription_id' => null])->save();
        }
        // past_due / incomplete: leave the plan in place (Stripe is retrying).
    }

    /** The planner subscription was cancelled — revert to the free plan. */
    protected function onSubscriptionEnded($subscription): void
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();

        $user?->forceFill([
            'plan' => config('plans.default'),
            'stripe_subscription_id' => null,
        ])->save();
    }

    protected function onAccountUpdated($account): void
    {
        $vendor = VendorProfile::where('stripe_account_id', $account->id)->first();

        $vendor?->forceFill([
            'stripe_charges_enabled' => (bool) ($account->charges_enabled ?? false),
            'stripe_details_submitted' => (bool) ($account->details_submitted ?? false),
        ])->save();
    }

    protected function onChargeRefunded($charge): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $charge->payment_intent)
            ->where('type', '!=', PaymentType::Refund->value)
            ->first();

        if ($payment === null) {
            return;
        }

        $refunded = (int) ($charge->amount_refunded ?? 0);

        Payment::create([
            'booking_id' => $payment->booking_id,
            'type' => PaymentType::Refund->value,
            'amount_cents' => $refunded,
            'status' => PaymentStatus::Refunded->value,
            'currency' => $payment->currency,
            'stripe_payment_intent_id' => $charge->payment_intent,
        ]);

        $payment->update(['status' => PaymentStatus::Refunded->value]);

        // A full refund of the charge cancels the booking.
        if (($charge->refunded ?? false) === true && $payment->booking) {
            $payment->booking->update(['status' => BookingStatus::Cancelled->value]);
            Log::info('Booking cancelled after full refund', ['booking_id' => $payment->booking_id]);
        }
    }

    /**
     * A cardholder opened a dispute (chargeback). Alert admins so someone can
     * submit evidence in Stripe before the deadline — liability is settled when
     * the dispute closes (see onDisputeClosed).
     */
    protected function onDisputeCreated($dispute): void
    {
        $booking = $this->bookingForCharge($dispute->payment_intent ?? null);

        Log::critical('Payment dispute opened', [
            'payment_intent' => $dispute->payment_intent ?? null,
            'amount' => $dispute->amount ?? null,
            'booking_id' => $booking?->id,
        ]);

        if ($booking !== null) {
            Notification::send(
                User::where('is_admin', true)->get(),
                new PaymentDisputeOpened($booking, (int) ($dispute->amount ?? 0)),
            );
        }
    }

    /**
     * A dispute closed. If it was lost, the platform's balance was debited — so
     * reverse the vendor's transfer to recover their share of the loss and cancel
     * the booking. A won dispute needs no action (Stripe restores the funds).
     */
    protected function onDisputeClosed($dispute): void
    {
        if (($dispute->status ?? null) !== 'lost') {
            return;
        }

        // Claw the vendor's payout back for the lost amount, if we can resolve
        // the transfer behind the disputed charge.
        try {
            $chargeId = $dispute->charge ?? null;
            if ($chargeId !== null) {
                $charge = $this->client()->charges->retrieve($chargeId);
                if (! empty($charge->transfer)) {
                    $this->client()->transfers->createReversal($charge->transfer, [
                        'amount' => (int) ($dispute->amount ?? 0),
                        'refund_application_fee' => true,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $booking = $this->bookingForCharge($dispute->payment_intent ?? null);
        if ($booking !== null) {
            $booking->update(['status' => BookingStatus::Cancelled->value]);
            Log::warning('Booking cancelled after lost dispute', ['booking_id' => $booking->id]);
        }
    }

    /** The booking behind a charge's payment intent, if any. */
    protected function bookingForCharge(?string $paymentIntent): ?Booking
    {
        if ($paymentIntent === null) {
            return null;
        }

        return Payment::where('stripe_payment_intent_id', $paymentIntent)
            ->where('type', '!=', PaymentType::Refund->value)
            ->first()?->booking;
    }
}
