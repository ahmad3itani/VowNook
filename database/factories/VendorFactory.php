<?php

namespace Database\Factories;

use App\Enums\VendorCategory;
use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->company(),
            'category' => fake()->randomElement(VendorCategory::cases()),
            'status' => fake()->randomElement(VendorStatus::cases()),
            'rating' => fake()->optional()->numberBetween(1, 5),
            'price_level' => fake()->optional()->numberBetween(1, 4),
            'contact_name' => fake()->name(),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'website' => fake()->optional()->url(),
            'cost_cents' => fake()->optional()->numberBetween(50000, 1500000),
            'paid_cents' => 0,
            'notes' => null,
        ];
    }

    public function booked(): static
    {
        return $this->state(fn () => ['status' => VendorStatus::Booked]);
    }
}
