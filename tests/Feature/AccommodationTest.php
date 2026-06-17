<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingAccommodation;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccommodationTest extends TestCase
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

    public function test_free_couple_is_redirected_from_travel(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($user)->get('/travel')->assertRedirect(route('plan.edit'));
    }

    public function test_couple_can_create_an_accommodation(): void
    {
        [$user, $wedding] = $this->premiumCouple();

        $this->actingAs($user)->post('/travel', [
            'name' => 'The Walper Hotel',
            'type' => 'hotel',
            'price_note' => 'from $159/night',
            'block_code' => 'SMITHWED2026',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('wedding_accommodations', [
            'wedding_id' => $wedding->id, 'name' => 'The Walper Hotel', 'type' => 'hotel',
        ]);
    }

    public function test_couple_can_update_and_delete_an_accommodation(): void
    {
        [$user, $wedding] = $this->premiumCouple();
        $stay = WeddingAccommodation::create(['wedding_id' => $wedding->id, 'name' => 'Inn', 'type' => 'hotel']);

        $this->actingAs($user)->put("/travel/{$stay->id}", ['name' => 'Grand Inn', 'type' => 'rental'])->assertRedirect();
        $this->assertDatabaseHas('wedding_accommodations', ['id' => $stay->id, 'name' => 'Grand Inn', 'type' => 'rental']);

        $this->actingAs($user)->delete("/travel/{$stay->id}")->assertRedirect();
        $this->assertDatabaseMissing('wedding_accommodations', ['id' => $stay->id]);
    }

    public function test_travel_notes_save_to_the_website(): void
    {
        [$user, $wedding] = $this->premiumCouple();

        $this->actingAs($user)->put('/travel/notes', ['travel_notes' => 'Free parking on-site.'])->assertRedirect();

        $this->assertDatabaseHas('wedding_websites', [
            'wedding_id' => $wedding->id, 'travel_notes' => 'Free parking on-site.',
        ]);
    }

    public function test_a_foreign_accommodation_cannot_be_edited(): void
    {
        [$user] = $this->premiumCouple();
        $foreign = WeddingAccommodation::create([
            'wedding_id' => Wedding::factory()->create()->id, 'name' => 'X', 'type' => 'hotel',
        ]);

        $this->actingAs($user)->put("/travel/{$foreign->id}", ['name' => 'Hijack', 'type' => 'hotel'])
            ->assertNotFound();
    }

    public function test_published_website_exposes_active_accommodations_and_notes(): void
    {
        $wedding = Wedding::factory()->create();
        WeddingWebsite::factory()->create([
            'wedding_id' => $wedding->id, 'is_published' => true, 'travel_notes' => 'Shuttle at 3:30 PM.',
        ]);
        WeddingAccommodation::create(['wedding_id' => $wedding->id, 'name' => 'Host Hotel', 'type' => 'hotel', 'is_active' => true]);
        WeddingAccommodation::create(['wedding_id' => $wedding->id, 'name' => 'Hidden', 'type' => 'hotel', 'is_active' => false]);

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/website')
                ->where('travel.notes', 'Shuttle at 3:30 PM.')
                ->has('travel.stays', 1)
                ->where('travel.stays.0.name', 'Host Hotel')
            );
    }
}
