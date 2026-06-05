<?php

namespace Database\Factories;

use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BudgetItem>
 */
class BudgetItemFactory extends Factory
{
    public function definition(): array
    {
        $estimated = fake()->numberBetween(50, 5000) * 100;

        return [
            'wedding_id' => Wedding::factory(),
            'category_id' => null,
            'name' => fake()->words(2, true),
            'estimated_cents' => $estimated,
            'actual_cents' => null,
            'paid_cents' => 0,
            'due_date' => null,
            'notes' => null,
        ];
    }
}
