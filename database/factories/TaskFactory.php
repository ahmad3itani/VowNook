<?php

namespace Database\Factories;

use App\Enums\TaskCategory;
use App\Enums\TaskPriority;
use App\Models\Task;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'assigned_to' => null,
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(TaskCategory::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->optional()->dateTimeBetween('-1 month', '+6 months'),
            'is_complete' => false,
            'completed_at' => null,
            'notes' => null,
        ];
    }

    public function complete(): static
    {
        return $this->state(fn () => [
            'is_complete' => true,
            'completed_at' => now(),
        ]);
    }
}
