<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->numberBetween(50, 1200) * 1000;

        return [
            'inquiry_id' => Inquiry::factory()->accepted(),
            // The offer, wedding, and vendor profile are derived from the inquiry
            // so a default factory booking is internally consistent.
            'offer_id' => fn (array $attributes) => Offer::factory()->accepted()->create([
                'inquiry_id' => $attributes['inquiry_id'],
                'total_cents' => $total,
                'deposit_cents' => (int) round($total * 0.25),
            ])->id,
            'wedding_id' => fn (array $attributes) => Inquiry::find($attributes['inquiry_id'])->wedding_id,
            'vendor_profile_id' => fn (array $attributes) => Inquiry::find($attributes['inquiry_id'])->vendor_profile_id,
            'vendor_id' => null,
            'total_cents' => $total,
            'deposit_cents' => (int) round($total * 0.25),
            'platform_fee_cents' => (int) round($total * 0.10),
            'status' => BookingStatus::PendingPayment,
            'stripe_payment_intent_id' => null,
        ];
    }

    public function depositPaid(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::DepositPaid]);
    }

    public function paidInFull(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::PaidInFull]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Cancelled]);
    }
}
