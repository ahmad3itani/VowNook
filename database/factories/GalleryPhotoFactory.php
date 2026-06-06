<?php

namespace Database\Factories;

use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\GalleryPhoto>
 */
class GalleryPhotoFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->word().'.jpg';

        return [
            'wedding_id' => Wedding::factory(),
            'path' => 'galleries/test/'.fake()->uuid().'.jpg',
            'original_name' => $name,
            'mime' => 'image/jpeg',
            'size' => fake()->numberBetween(50_000, 5_000_000),
            'caption' => fake()->optional()->sentence(4),
        ];
    }
}
