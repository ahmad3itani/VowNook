<?php

namespace Database\Factories;

use App\Enums\SeatingElementType;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SeatingElement>
 */
class SeatingElementFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(SeatingElementType::cases());
        [$w, $h] = $type->defaultSize();

        return [
            'wedding_id' => Wedding::factory(),
            'type' => $type,
            'label' => null,
            'position_x' => fake()->numberBetween(10, 80),
            'position_y' => fake()->numberBetween(10, 80),
            'width' => $w,
            'height' => $h,
            'rotation' => 0,
        ];
    }
}
