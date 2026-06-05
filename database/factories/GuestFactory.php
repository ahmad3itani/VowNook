<?php

namespace Database\Factories;

use App\Enums\AgeGroup;
use App\Enums\GuestSide;
use App\Enums\RsvpStatus;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Guest>
 */
class GuestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'group_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'side' => fake()->randomElement(GuestSide::cases()),
            'age_group' => AgeGroup::Adult,
            'is_plus_one' => false,
            'rsvp_status' => fake()->randomElement(RsvpStatus::cases()),
            'meal_choice' => null,
            'dietary_notes' => null,
            'invited_at' => null,
            'notes' => null,
        ];
    }

    public function attending(): static
    {
        return $this->state(fn () => ['rsvp_status' => RsvpStatus::Attending]);
    }

    public function declined(): static
    {
        return $this->state(fn () => ['rsvp_status' => RsvpStatus::Declined]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['rsvp_status' => RsvpStatus::Pending]);
    }
}
