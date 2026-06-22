<?php

namespace Tests\Feature;

use App\Support\Affiliates\TravelPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TravelPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_flight_price_parses_the_cheapest_fare(): void
    {
        config([
            'affiliates.travelpayouts.api_token' => 'tok',
            'affiliates.travelpayouts.api_base' => 'https://api.travelpayouts.com',
        ]);
        Http::fake(['api.travelpayouts.com/v1/prices/cheap*' => Http::response([
            'success' => true,
            'data' => ['OGG' => [
                '0' => ['price' => 980, 'airline' => 'AC'],
                '1' => ['price' => 870, 'airline' => 'WS'],
            ]],
        ])]);

        $r = (new TravelPricing)->flightPrice('yyz', 'OGG', '2026-10-01', '2026-10-09');

        $this->assertTrue($r['found']);
        $this->assertSame(87000, $r['price_cents']);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'origin=YYZ') && str_contains($req->url(), 'token=tok'));
    }

    public function test_hotel_price_parses_the_cheapest_stay(): void
    {
        config([
            'affiliates.travelpayouts.api_token' => 'tok',
            'affiliates.travelpayouts.hotellook_base' => 'https://engine.hotellook.com',
        ]);
        Http::fake(['engine.hotellook.com/api/v2/cache.json*' => Http::response([
            ['hotelName' => 'A', 'priceFrom' => 320.5],
            ['hotelName' => 'B', 'priceFrom' => 280],
        ])]);

        $r = (new TravelPricing)->hotelPrice('Maui', '2026-10-01', '2026-10-09');

        $this->assertTrue($r['found']);
        $this->assertSame(28000, $r['price_cents']);
    }

    public function test_not_configured_returns_not_found_without_calling(): void
    {
        config(['affiliates.travelpayouts.api_token' => null]);
        Http::fake();

        $this->assertFalse((new TravelPricing)->flightPrice('YYZ', 'OGG', '2026-10-01', '2026-10-09')['found']);
        $this->assertFalse((new TravelPricing)->hotelPrice('Maui', '2026-10-01', '2026-10-09')['found']);

        Http::assertNothingSent();
    }

    public function test_empty_data_is_graceful(): void
    {
        config(['affiliates.travelpayouts.api_token' => 'tok']);
        Http::fake(['*' => Http::response(['success' => true, 'data' => []])]);

        $this->assertFalse((new TravelPricing)->flightPrice('YYZ', 'XXX', '2026-10-01', '2026-10-09')['found']);
    }
}
