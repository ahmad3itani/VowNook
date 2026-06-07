<?php

namespace Database\Factories;

use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SeatingLayout>
 */
class SeatingLayoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'room_width' => 40,
            'room_height' => 30,
        ];
    }
}
