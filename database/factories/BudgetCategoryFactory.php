<?php

namespace Database\Factories;

use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BudgetCategory>
 */
class BudgetCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->randomElement(['Venue', 'Catering', 'Attire', 'Flowers', 'Photography', 'Music']),
            'sort_order' => 0,
        ];
    }
}
