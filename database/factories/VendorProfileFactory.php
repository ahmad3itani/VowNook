<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\VendorCategory;
use App\Enums\VendorProfileStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorProfile>
 */
class VendorProfileFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement([
            fake()->lastName().' & Co.',
            fake()->firstName()."'s ".fake()->randomElement(['Studio', 'Atelier', 'Events', 'Creations']),
            fake()->word().' '.fake()->randomElement(['Bloom', 'Light', 'Sound', 'Table', 'Lane']).' Weddings',
            'The '.fake()->word().' '.fake()->randomElement(['Collective', 'House', 'Company']),
        ]);
        $name = Str::title($name);

        return [
            'user_id' => User::factory(['account_type' => AccountType::Vendor]),
            'business_name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'category' => fake()->randomElement(VendorCategory::cases()),
            'tagline' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'logo_path' => null,
            'cover_path' => null,
            'city' => fake()->city(),
            'region' => fake()->randomElement(['ON', 'QC', 'BC', 'AB', 'NS']),
            'country' => 'CA',
            'service_area' => 'Greater '.fake()->city().' area',
            'base_price_cents' => fake()->numberBetween(50, 500) * 1000,
            'price_unit' => fake()->randomElement(['per_event', 'per_hour', 'per_person']),
            'website' => fake()->optional()->url(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'socials' => null,
            'status' => VendorProfileStatus::Published,
            'is_accepting_bookings' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => VendorProfileStatus::Draft]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => ['status' => VendorProfileStatus::PendingReview]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => VendorProfileStatus::Published]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => VendorProfileStatus::Suspended]);
    }
}
