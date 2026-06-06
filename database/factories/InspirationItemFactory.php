<?php

namespace Database\Factories;

use App\Enums\InspirationCategory;
use App\Models\InspirationItem;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspirationItem>
 */
class InspirationItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'title' => fake()->sentence(3),
            'category' => fake()->randomElement(InspirationCategory::cases()),
            'image_url' => fake()->optional()->imageUrl(),
            'link_url' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
