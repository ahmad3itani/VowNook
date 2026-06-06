<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_index_is_scoped_to_the_active_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Vendor::factory()->count(2)->create(['wedding_id' => $wedding->id]);
        Vendor::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/vendors')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('vendors/index')
                ->has('vendors', 2)
            );
    }

    public function test_member_can_create_a_vendor_with_dollar_amounts(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/vendors', [
            'name' => 'Bloom & Co',
            'category' => 'florist',
            'status' => 'booked',
            'cost_amount' => 1800,
            'paid_amount' => 500.25,
        ])->assertRedirect();

        $this->assertDatabaseHas('vendors', [
            'wedding_id' => $wedding->id,
            'name' => 'Bloom & Co',
            'category' => 'florist',
            'status' => 'booked',
            'cost_cents' => 180000,
            'paid_cents' => 50025,
        ]);
    }

    public function test_viewer_cannot_create_a_vendor(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/vendors', [
            'name' => 'Nope',
            'category' => 'other',
            'status' => 'researching',
            'paid_amount' => 0,
        ])->assertForbidden();
    }

    public function test_cannot_update_a_vendor_from_another_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = Vendor::factory()->create();

        $this->actingAs($user)->put("/vendors/{$foreign->id}", [
            'name' => 'Hijack',
            'category' => 'other',
            'status' => 'researching',
            'paid_amount' => 0,
        ])->assertNotFound();
    }

    public function test_invalid_category_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/vendors', [
            'name' => 'Bad',
            'category' => 'spaceship',
            'status' => 'researching',
            'paid_amount' => 0,
        ])->assertSessionHasErrors('category');
    }

    public function test_stats_are_computed(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Vendor::factory()->booked()->create([
            'wedding_id' => $wedding->id,
            'cost_cents' => 100000,
            'paid_cents' => 40000,
        ]);
        Vendor::factory()->create([
            'wedding_id' => $wedding->id,
            'status' => VendorStatus::Quoted,
            'cost_cents' => 50000,
            'paid_cents' => 0,
        ]);

        $this->actingAs($user)
            ->get('/vendors')
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 2)
                ->where('stats.booked', 1)
                ->where('stats.contracted', 1500)
                ->where('stats.paid', 400)
            );
    }

    public function test_member_can_delete_a_vendor(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $vendor = Vendor::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)
            ->delete("/vendors/{$vendor->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }
}
