<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealSelectionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    protected function ownerWithWedding(array $meals = []): array
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create([
            'owner_id' => $owner->id,
            'settings' => $meals ? ['meals' => $meals] : null,
        ]);
        $wedding->members()->attach($owner->id, ['role' => Role::Owner->value, 'accepted_at' => now()]);
        $owner->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$owner, $wedding];
    }

    protected function mealConfig(): array
    {
        return [
            'appetizer' => ['enabled' => false, 'options' => ['Soup']],
            'main' => ['enabled' => true, 'options' => ['Chicken', 'Beef', 'Vegetarian']],
            'dessert' => ['enabled' => true, 'options' => ['Cake', 'Sorbet']],
        ];
    }

    // ── Couple configures ────────────────────────────────────────────────────

    public function test_couple_can_save_meal_options(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding();

        $this->actingAs($owner)->put('/guests/meal-options', [
            'meals' => [
                'main' => ['enabled' => true, 'options' => ['Chicken', '  Beef  ', 'chicken', '']],
                'dessert' => ['enabled' => true, 'options' => ['Cake']],
                'appetizer' => ['enabled' => false, 'options' => []],
            ],
        ])->assertRedirect();

        $meals = $wedding->fresh()->settings['meals'];
        // Trimmed, de-duped (case-insensitive), blanks dropped.
        $this->assertSame(['Chicken', 'Beef'], $meals['main']['options']);
        $this->assertTrue($meals['main']['enabled']);
        $this->assertFalse($meals['appetizer']['enabled']);
    }

    public function test_non_writer_cannot_save_meal_options(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value, 'accepted_at' => now()]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->put('/guests/meal-options', [
            'meals' => ['main' => ['enabled' => true, 'options' => ['X']]],
        ])->assertForbidden();
    }

    // ── RSVP form exposes enabled courses ────────────────────────────────────

    public function test_rsvp_page_exposes_only_enabled_courses(): void
    {
        [, $wedding] = $this->ownerWithWedding($this->mealConfig());

        $this->get("/w/{$wedding->slug}/rsvp")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('public/rsvp')
                ->has('meals', 2) // main + dessert (appetizer disabled)
                ->where('meals.0.course', 'main')
                ->where('meals.1.course', 'dessert')
                ->where('meals.0.options', ['Chicken', 'Beef', 'Vegetarian']));
    }

    // ── Guest picks at RSVP ──────────────────────────────────────────────────

    public function test_guest_can_choose_meals_from_the_list(): void
    {
        [, $wedding] = $this->ownerWithWedding($this->mealConfig());
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id, 'first_name' => 'Sam']);

        $this->post("/w/{$wedding->slug}/rsvp/respond", [
            'guest_id' => $guest->id,
            'rsvp_status' => 'attending',
            'meal_choice' => 'Beef',
            'dessert_choice' => 'Sorbet',
            'dietary_notes' => 'Nut allergy',
        ])->assertRedirect();

        $guest->refresh();
        $this->assertSame('Beef', $guest->meal_choice);
        $this->assertSame('Sorbet', $guest->dessert_choice);
        $this->assertSame('Nut allergy', $guest->dietary_notes);
    }

    public function test_a_meal_choice_outside_the_list_is_rejected(): void
    {
        [, $wedding] = $this->ownerWithWedding($this->mealConfig());
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->post("/w/{$wedding->slug}/rsvp/respond", [
            'guest_id' => $guest->id,
            'rsvp_status' => 'attending',
            'meal_choice' => 'Lobster', // not an option
        ])->assertSessionHasErrors('meal_choice');

        $this->assertNull($guest->fresh()->meal_choice);
    }

    public function test_a_disabled_course_choice_is_ignored(): void
    {
        [, $wedding] = $this->ownerWithWedding($this->mealConfig()); // appetizer disabled
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->post("/w/{$wedding->slug}/rsvp/respond", [
            'guest_id' => $guest->id,
            'rsvp_status' => 'attending',
            'meal_choice' => 'Chicken',
            'appetizer_choice' => 'Soup', // course is off — must be ignored, not stored
        ])->assertRedirect();

        $guest->refresh();
        $this->assertSame('Chicken', $guest->meal_choice);
        $this->assertNull($guest->appetizer_choice);
    }

    public function test_main_stays_backward_compatible_for_dashboard_count(): void
    {
        // Default config (no settings) = main enabled with no options ⇒ free text
        // still accepted, so existing data/flows keep working.
        [$owner, $wedding] = $this->ownerWithWedding();
        $guest = Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->post("/w/{$wedding->slug}/rsvp/respond", [
            'guest_id' => $guest->id,
            'rsvp_status' => 'attending',
            'meal_choice' => 'Whatever the kitchen sends',
        ])->assertRedirect();

        $this->assertSame('Whatever the kitchen sends', $guest->fresh()->meal_choice);
    }
}
