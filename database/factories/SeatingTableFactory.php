<?php

namespace Database\Factories;

use App\Enums\TableShape;
use App\Models\SeatingTable;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatingTable>
 */
class SeatingTableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => 'Table '.fake()->unique()->numberBetween(1, 40),
            'shape' => fake()->randomElement(TableShape::cases()),
            'capacity' => fake()->randomElement([6, 8, 10, 12]),
            'position_x' => fake()->numberBetween(10, 80),
            'position_y' => fake()->numberBetween(10, 80),
            'notes' => null,
        ];
    }
}
