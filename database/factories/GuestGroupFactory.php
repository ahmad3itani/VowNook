<?php

namespace Database\Factories;

use App\Models\GuestGroup;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestGroup>
 */
class GuestGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->lastName().' Family',
            'notes' => null,
        ];
    }
}
