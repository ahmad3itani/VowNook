<?php

namespace Tests\Feature;

use App\Models\HoneymoonPlan;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AffiliateDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/affiliates')->assertForbidden();
    }

    public function test_admin_sees_adoption_metrics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $w1 = Wedding::factory()->create();
        WeddingWebsite::factory()->create([
            'wedding_id' => $w1->id, 'is_published' => true, 'show_travel_stays' => true,
            'venue_name' => 'Riverside Hall', 'nearest_airport' => 'YYZ',
        ]);

        HoneymoonPlan::create(['wedding_id' => Wedding::factory()->create()->id, 'destination' => 'Maui']);
        HoneymoonPlan::create(['wedding_id' => Wedding::factory()->create()->id]); // no destination yet

        $this->actingAs($admin)->get('/admin/affiliates')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/affiliates')
                ->where('adoption.stays_live', 1)
                ->where('adoption.flights_live', 1)
                ->where('adoption.honeymoons', 2)
                ->where('adoption.honeymoons_planned', 1)
            );
    }

    public function test_travelpayouts_balance_pulls_live_when_configured(): void
    {
        config([
            'affiliates.travelpayouts.api_token' => 'tok-1',
            'affiliates.travelpayouts.api_base' => 'https://api.travelpayouts.com',
        ]);
        Http::fake([
            'api.travelpayouts.com/finance/v2/get_user_balance' => Http::response(['amount' => 1234.5, 'currency' => 'usd']),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/affiliates')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('travelpayouts.balance.connected', true)
                ->where('travelpayouts.balance.amount', 1234.5)
                ->where('travelpayouts.balance.currency', 'USD')
            );

        Http::assertSent(fn ($req) => $req->hasHeader('X-Access-Token', 'tok-1'));
    }

    public function test_travelpayouts_not_connected_without_a_token(): void
    {
        config(['affiliates.travelpayouts.api_token' => null]);
        Http::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/affiliates')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('travelpayouts.balance.connected', false));

        Http::assertNothingSent();
    }
}
