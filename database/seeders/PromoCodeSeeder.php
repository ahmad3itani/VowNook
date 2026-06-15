<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::updateOrCreate(
            ['code' => 'WEDDING2026'],
            [
                'kind' => 'comp_plan',
                'plan' => 'premium',
                'duration_days' => 365,
                'max_redemptions' => null,
                'is_active' => true,
                'note' => 'Launch promo — one year of Premium, free.',
            ],
        );
    }
}
