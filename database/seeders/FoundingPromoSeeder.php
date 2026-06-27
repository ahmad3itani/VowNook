<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

/**
 * The launch "founding members" offer: the first 50 couples to redeem FOUNDING50
 * get the Atelier (premium) plan free for life — Atelier is a one-time unlock, so
 * a very long comp window is effectively permanent. The 50-redemption cap closes
 * the offer automatically. Idempotent (updateOrCreate by code), so re-running
 * never resets the redemption count.
 */
class FoundingPromoSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::updateOrCreate(
            ['code' => 'FOUNDING50'],
            [
                'kind' => 'comp_plan',
                'plan' => 'premium',      // Atelier
                'duration_days' => 36500, // ~100 years = free for life
                'max_redemptions' => 50,
                'is_active' => true,
                'note' => 'Founding 50 — free Atelier for life, for our first members.',
            ],
        );
    }
}
