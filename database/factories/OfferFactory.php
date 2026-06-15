<?php

namespace Database\Factories;

use App\Enums\OfferStatus;
use App\Models\Inquiry;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->numberBetween(50, 1200) * 1000;

        return [
            'inquiry_id' => Inquiry::factory(),
            'total_cents' => $total,
            'deposit_cents' => (int) round($total * 0.25),
            'line_items' => [
                ['name' => 'Base package', 'amount_cents' => $total, 'qty' => 1],
            ],
            'terms' => fake()->optional()->sentence(12),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => OfferStatus::Sent,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => OfferStatus::Accepted]);
    }

    public function declined(): static
    {
        return $this->state(fn () => ['status' => OfferStatus::Declined]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn () => ['status' => OfferStatus::Withdrawn]);
    }
}
