<?php

namespace Tests\Feature;

use App\Models\Gift;
use App\Models\Guest;
use App\Models\RegistryFund;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftTest extends TestCase
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

    public function test_free_couple_is_redirected_from_gifts(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)->get('/gifts')->assertRedirect(route('plan.edit'));
    }

    public function test_a_registry_contribution_auto_creates_a_gift(): void
    {
        [, $wedding] = $this->premiumCouple();
        $fund = RegistryFund::create(['wedding_id' => $wedding->id, 'title' => 'Honeymoon', 'type' => 'honeymoon']);

        $this->post("/w/{$wedding->slug}/registry/funds/{$fund->id}/contribute", [
            'amount_cents' => 7500, 'contributor_name' => 'Aunt May', 'message' => 'Congrats!',
        ])->assertRedirect();

        $this->assertDatabaseHas('gifts', [
            'wedding_id' => $wedding->id,
            'from_name' => 'Aunt May',
            'kind' => 'fund',
            'amount_cents' => 7500,
            'thank_you_sent' => false,
        ]);
    }

    public function test_couple_can_add_a_manual_gift(): void
    {
        [$user, $wedding] = $this->premiumCouple();

        $this->actingAs($user)->post('/gifts', [
            'from_name' => 'The Smiths', 'kind' => 'physical', 'amount_cents' => 12000, 'notes' => 'Stand mixer',
        ])->assertRedirect();

        $this->assertDatabaseHas('gifts', ['wedding_id' => $wedding->id, 'from_name' => 'The Smiths', 'kind' => 'physical']);
    }

    public function test_couple_can_toggle_thank_you_sent(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        $gift = Gift::create(['wedding_id' => $wedding->id, 'from_name' => 'X', 'kind' => 'cash', 'thank_you_sent' => false]);

        $this->actingAs($user)->patch("/gifts/{$gift->id}/thank-you", ['thank_you_sent' => true])->assertRedirect();
        $this->assertTrue($gift->fresh()->thank_you_sent);

        $this->actingAs($user)->patch("/gifts/{$gift->id}/thank-you", ['thank_you_sent' => false])->assertRedirect();
        $this->assertFalse($gift->fresh()->thank_you_sent);
    }

    public function test_linking_a_foreign_guest_is_rejected(): void
    {
        [$user] = $this->premiumCouple();
        $foreignGuest = Guest::factory()->create();

        $this->actingAs($user)->post('/gifts', [
            'from_name' => 'X', 'kind' => 'physical', 'guest_id' => $foreignGuest->id,
        ])->assertStatus(422);
    }

    public function test_a_foreign_gift_cannot_be_modified(): void
    {
        [$user] = $this->premiumCouple();
        $foreign = Gift::create(['wedding_id' => Wedding::factory()->create()->id, 'from_name' => 'X', 'kind' => 'cash']);

        $this->actingAs($user)->patch("/gifts/{$foreign->id}/thank-you", ['thank_you_sent' => true])->assertNotFound();
    }

    public function test_index_summarizes_pending_and_cash(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        Gift::create(['wedding_id' => $wedding->id, 'kind' => 'fund', 'amount_cents' => 5000, 'thank_you_sent' => false]);
        Gift::create(['wedding_id' => $wedding->id, 'kind' => 'cash', 'amount_cents' => 10000, 'thank_you_sent' => true]);
        Gift::create(['wedding_id' => $wedding->id, 'kind' => 'physical', 'thank_you_sent' => false]);

        $this->actingAs($user)->get('/gifts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('gifts/index')
                ->where('summary.total', 3)
                ->where('summary.pending', 2)
                ->where('summary.cash_cents', 15000)
            );
    }
}
