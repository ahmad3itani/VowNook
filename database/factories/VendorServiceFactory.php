<?php

namespace Database\Factories;

use App\Models\VendorProfile;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorService>
 */
class VendorServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_profile_id' => VendorProfile::factory(),
            'name' => fake()->randomElement([
                'Essential Package', 'Signature Package', 'Premium Package',
                'Full-Day Coverage', 'Elopement Special', 'Weekend Celebration',
            ]),
            'description' => fake()->sentence(10),
            'price_cents' => fake()->numberBetween(30, 800) * 1000,
            'price_unit' => fake()->randomElement(['per_event', 'per_hour', 'per_person']),
            'price_type' => fake()->randomElement(['fixed', 'from']),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function quoteOnly(): static
    {
        return $this->state(fn () => [
            'price_cents' => null,
            'price_type' => 'quote_only',
        ]);
    }
}
