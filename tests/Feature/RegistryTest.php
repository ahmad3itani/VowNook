<?php

namespace Tests\Feature;

use App\Models\RegistryFund;
use App\Models\RegistryItem;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistryTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    private function premiumCouple(): array
    {
        $user = User::factory()->plan('premium')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_free_couple_is_redirected_from_registry(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)->get('/registry')->assertRedirect(route('plan.edit'));
    }

    public function test_couple_can_create_a_fund_and_item(): void
    {
        [$user, $wedding] = $this->premiumCouple();

        $this->actingAs($user)->post('/registry/funds', [
            'title' => 'Honeymoon', 'type' => 'honeymoon', 'goal_cents' => 500000, 'is_active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('registry_funds', ['wedding_id' => $wedding->id, 'title' => 'Honeymoon']);

        $this->actingAs($user)->post('/registry/items', [
            'name' => 'Stand mixer', 'quantity' => 1, 'price_cents' => 30000,
        ])->assertRedirect();
        $this->assertDatabaseHas('registry_items', ['wedding_id' => $wedding->id, 'name' => 'Stand mixer']);
    }

    public function test_guest_contribution_logs_and_bumps_raised(): void
    {
        [, $wedding] = $this->premiumCouple();
        $fund = RegistryFund::create([
            'wedding_id' => $wedding->id, 'title' => 'Honeymoon', 'type' => 'honeymoon', 'raised_cents' => 0,
        ]);

        $this->post("/w/{$wedding->slug}/registry/funds/{$fund->id}/contribute", [
            'amount_cents' => 5000, 'contributor_name' => 'Aunt May', 'message' => 'Congrats!',
        ])->assertRedirect();

        $this->assertDatabaseHas('registry_contributions', [
            'registry_fund_id' => $fund->id, 'amount_cents' => 5000, 'contributor_name' => 'Aunt May',
        ]);
        $this->assertSame(5000, $fund->fresh()->raised_cents);
    }

    public function test_guest_can_claim_an_item_until_quantity_reached(): void
    {
        [, $wedding] = $this->premiumCouple();
        $item = RegistryItem::create([
            'wedding_id' => $wedding->id, 'name' => 'Mixer', 'quantity' => 1, 'claimed_count' => 0,
        ]);

        $this->post("/w/{$wedding->slug}/registry/items/{$item->id}/claim")->assertRedirect();
        $this->assertSame(1, $item->fresh()->claimed_count);

        // Already fully claimed → no further increment.
        $this->post("/w/{$wedding->slug}/registry/items/{$item->id}/claim")->assertRedirect();
        $this->assertSame(1, $item->fresh()->claimed_count);
    }

    public function test_contribution_to_a_foreign_fund_is_blocked(): void
    {
        [, $wedding] = $this->premiumCouple();
        $foreign = RegistryFund::create([
            'wedding_id' => Wedding::factory()->create()->id, 'title' => 'X', 'type' => 'cash',
        ]);

        $this->post("/w/{$wedding->slug}/registry/funds/{$foreign->id}/contribute", ['amount_cents' => 5000])
            ->assertNotFound();
    }
}
