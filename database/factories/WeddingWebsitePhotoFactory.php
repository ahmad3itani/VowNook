<?php

namespace Database\Factories;

use App\Models\WeddingWebsite;
use App\Models\WeddingWebsitePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WeddingWebsitePhoto>
 */
class WeddingWebsitePhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_website_id' => WeddingWebsite::factory(),
            'path' => 'wedding-websites/gallery/'.Str::random(20).'.jpg',
            'caption' => fake()->optional()->words(3, true),
            'sort_order' => 0,
        ];
    }
}
