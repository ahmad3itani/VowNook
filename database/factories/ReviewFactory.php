<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            // Wedding, vendor profile, and couple are derived from the booking
            // so a default factory review is internally consistent.
            'wedding_id' => fn (array $attributes) => Booking::find($attributes['booking_id'])->wedding_id,
            'vendor_profile_id' => fn (array $attributes) => Booking::find($attributes['booking_id'])->vendor_profile_id,
            'couple_user_id' => fn (array $attributes) => Booking::find($attributes['booking_id'])->inquiry->couple_user_id,
            'rating' => fake()->numberBetween(3, 5),
            'body' => fake()->paragraph(),
            'vendor_response' => null,
            'vendor_responded_at' => null,
        ];
    }

    public function withResponse(): static
    {
        return $this->state(fn () => [
            'vendor_response' => fake()->paragraph(),
            'vendor_responded_at' => now(),
        ]);
    }
}
