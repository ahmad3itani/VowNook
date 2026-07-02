<?php

namespace Tests\Feature;

use App\Mail\ShopOrderDelivery;
use App\Models\ShopOrder;
use App\Support\Payments\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The VowNook Shop: static storefront, Stripe checkout contract, webhook
 * fulfilment + delivery email, and the signed download link.
 */
class ShopTest extends TestCase
{
    use RefreshDatabase;

    private function pendingOrder(array $overrides = []): ShopOrder
    {
        return ShopOrder::create(array_merge([
            'product_key' => 'The Invitation Suite',
            'product_name' => 'The Invitation Suite',
            'amount_cents' => 3200,
            'currency' => 'cad',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_the_storefront_serves_at_shop(): void
    {
        $response = $this->get('/shop');
        $response->assertOk();

        // BinaryFileResponse content isn't buffered into the test body — assert
        // against the actual file being served.
        $file = $response->baseResponse->getFile()->getPathname();
        $this->assertStringContainsString('VowNook', file_get_contents($file));
        $this->assertStringContainsString('/api/shop/checkout', file_get_contents($file));
    }

    public function test_checkout_rejects_an_unknown_product(): void
    {
        $this->postJson('/api/shop/checkout', ['product' => 'Not A Product'])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);

        $this->assertSame(0, ShopOrder::count());
    }

    public function test_checkout_degrades_when_stripe_is_not_configured(): void
    {
        config(['services.stripe.secret' => null]);

        $this->postJson('/api/shop/checkout', ['product' => 'The Invitation Suite'])
            ->assertStatus(503);

        $this->assertSame(0, ShopOrder::count());
    }

    public function test_checkout_creates_a_pending_order_and_returns_the_session_url(): void
    {
        $this->mock(StripeService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturnTrue();
            $mock->shouldReceive('shopCheckout')->once()->andReturn('https://checkout.stripe.test/cs_123');
        });

        $this->postJson('/api/shop/checkout', ['product' => 'Day-Of Printables'])
            ->assertOk()
            ->assertJson(['url' => 'https://checkout.stripe.test/cs_123']);

        $this->assertDatabaseHas('shop_orders', [
            'product_key' => 'Day-Of Printables',
            'amount_cents' => 2400,
            'status' => 'pending',
        ]);
    }

    public function test_the_webhook_fulfils_the_order_and_emails_the_download_link(): void
    {
        Mail::fake();
        config([
            'services.stripe.secret' => 'sk_test_x',
            'services.stripe.webhook_secret' => 'whsec_test',
        ]);

        $order = $this->pendingOrder(['stripe_session_id' => 'cs_shop_1']);

        $payload = json_encode([
            'id' => 'evt_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_shop_1',
                'object' => 'checkout.session',
                'amount_total' => 3200,
                'customer_details' => ['email' => 'bride@example.com'],
                'metadata' => ['shop_order_id' => (string) $order->id, 'product_name' => $order->product_name],
            ]],
        ]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test');

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $order->refresh();
        $this->assertSame('fulfilled', $order->status);
        $this->assertSame('bride@example.com', $order->email);
        $this->assertNotNull($order->fulfilled_at);

        Mail::assertSent(ShopOrderDelivery::class, fn ($mail) => $mail->hasTo('bride@example.com'));

        // Idempotent: a Stripe retry never double-sends.
        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        Mail::assertSentCount(1);
    }

    public function test_the_signed_download_streams_the_file_for_fulfilled_orders(): void
    {
        Storage::fake();
        Storage::put('shop-products/VowNook-Invitation-Suite.zip', 'zip-bytes');

        $order = $this->pendingOrder(['status' => 'fulfilled', 'fulfilled_at' => now()]);

        $url = URL::temporarySignedRoute('shop.download', now()->addDay(), ['order' => $order->id]);

        $this->get($url)->assertOk();
    }

    public function test_product_pages_serve_at_their_clean_urls(): void
    {
        $response = $this->get('/shop/p/invitation-suite');
        $response->assertOk();

        $html = file_get_contents($response->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('The Invitation Suite', $html);
        $this->assertStringContainsString('"@type":"Product"', $html);
        // Buy buttons are wired by the shared product-page script.
        $this->assertStringContainsString('/shop/assets/pdp.js', $html);

        $this->get('/shop/p/not-a-product')->assertNotFound();
    }

    public function test_the_sitemap_lists_the_product_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('/shop/p/invitation-suite'), false)
            ->assertSee(url('/shop/p/complete-collection'), false);
    }

    public function test_the_personaliser_unlock_requires_a_signature_and_a_fulfilled_order(): void
    {
        $fulfilled = $this->pendingOrder(['status' => 'fulfilled', 'fulfilled_at' => now()]);
        $pending = $this->pendingOrder();

        $url = URL::temporarySignedRoute('shop.unlocked', now()->addYear(), ['order' => $fulfilled->id]);
        $this->getJson($url)->assertOk()->assertJson(['ok' => true]);

        // Unsigned — blocked outright.
        $this->getJson("/api/shop/unlocked/{$fulfilled->id}")->assertStatus(403);

        // Signed but never paid — blocked.
        $url = URL::temporarySignedRoute('shop.unlocked', now()->addYear(), ['order' => $pending->id]);
        $this->getJson($url)->assertStatus(403);
    }

    public function test_the_delivery_email_carries_a_signed_personaliser_unlock(): void
    {
        $order = $this->pendingOrder(['status' => 'fulfilled', 'fulfilled_at' => now(), 'email' => 'bride@example.com']);

        $html = (new \App\Mail\ShopOrderDelivery($order))->render();

        $this->assertStringContainsString('customize.html?unlock=', $html);
        $this->assertStringContainsString('signature', $html);
    }

    public function test_the_download_rejects_unsigned_and_unfulfilled_requests(): void
    {
        Storage::fake();
        Storage::put('shop-products/VowNook-Invitation-Suite.zip', 'zip-bytes');

        $fulfilled = $this->pendingOrder(['status' => 'fulfilled', 'fulfilled_at' => now()]);
        $pending = $this->pendingOrder();

        // No signature — blocked outright.
        $this->get("/shop/download/{$fulfilled->id}")->assertStatus(403);

        // Signed but not paid — blocked.
        $url = URL::temporarySignedRoute('shop.download', now()->addDay(), ['order' => $pending->id]);
        $this->get($url)->assertStatus(403);
    }
}
