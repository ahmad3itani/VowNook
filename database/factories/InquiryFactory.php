<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'couple_user_id' => fn (array $attributes) => Wedding::find($attributes['wedding_id'])->owner_id
                ?? User::factory()->create()->id,
            'vendor_profile_id' => VendorProfile::factory(),
            'vendor_service_id' => null,
            'event_date' => fake()->dateTimeBetween('+2 months', '+18 months')->format('Y-m-d'),
            'guest_count' => fake()->numberBetween(40, 220),
            'budget_cents' => fake()->numberBetween(20, 200) * 10000,
            'message' => fake()->paragraph(),
            'status' => InquiryStatus::Requested,
        ];
    }

    public function offered(): static
    {
        return $this->state(fn () => ['status' => InquiryStatus::Offered]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => InquiryStatus::Accepted]);
    }

    public function declined(): static
    {
        return $this->state(fn () => ['status' => InquiryStatus::Declined]);
    }
}
