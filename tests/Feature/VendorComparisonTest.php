<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_compare_lists_vendors_in_the_active_category(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Vendor::factory()->count(2)->create(['wedding_id' => $wedding->id, 'category' => 'florist']);
        Vendor::factory()->create(['wedding_id' => $wedding->id, 'category' => 'catering']);

        $this->actingAs($user)
            ->get('/vendors/compare?category=florist')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vendors/compare')
                ->where('active', 'florist')
                ->has('vendors', 2)
                ->has('categories')
            );
    }

    public function test_compare_highlights_the_best_value_vendor(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $best = Vendor::factory()->create([
            'wedding_id' => $wedding->id,
            'category' => 'florist',
            'rating' => 5,
            'price_level' => 2,
            'cost_cents' => 200000,
        ]);
        Vendor::factory()->create([
            'wedding_id' => $wedding->id,
            'category' => 'florist',
            'rating' => 3,
            'price_level' => 4,
            'cost_cents' => 500000,
        ]);

        $this->actingAs($user)
            ->get('/vendors/compare?category=florist')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('bestValueId', $best->id));
    }

    public function test_compare_is_scoped_to_the_active_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Vendor::factory()->create(['wedding_id' => $wedding->id, 'category' => 'florist']);
        Vendor::factory()->count(3)->create(['category' => 'florist']);

        $this->actingAs($user)
            ->get('/vendors/compare?category=florist')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('vendors', 1));
    }

    public function test_rating_and_price_level_are_validated(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/vendors', [
            'name' => 'Bad Florist',
            'category' => 'florist',
            'status' => 'researching',
            'paid_amount' => 0,
            'rating' => 9,
            'price_level' => 7,
        ])->assertSessionHasErrors(['rating', 'price_level']);
    }

    public function test_a_vendor_can_be_saved_with_rating_and_price_level(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/vendors', [
            'name' => 'Petal Atelier',
            'category' => 'florist',
            'status' => 'quoted',
            'paid_amount' => 0,
            'rating' => 4,
            'price_level' => 3,
        ])->assertRedirect();

        $this->assertDatabaseHas('vendors', [
            'wedding_id' => $wedding->id,
            'name' => 'Petal Atelier',
            'rating' => 4,
            'price_level' => 3,
        ]);
    }

    public function test_comparison_pdf_downloads(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Vendor::factory()->count(2)->create(['wedding_id' => $wedding->id, 'category' => 'florist', 'rating' => 4]);

        $response = $this->actingAs($user)->get('/vendors/compare/pdf?category=florist');

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
