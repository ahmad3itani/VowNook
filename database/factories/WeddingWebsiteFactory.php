<?php

namespace Database\Factories;

use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WeddingWebsite>
 */
class WeddingWebsiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'is_published' => true,
            'headline' => "We're getting married!",
            'welcome_message' => fake()->sentence(12),
            'our_story' => fake()->paragraph(),
            'venue_name' => fake()->company().' Estate',
            'venue_address' => fake()->address(),
            'ceremony_time' => '4:00 PM',
            'dress_code' => 'Garden formal',
            'hero_image_url' => null,
        ];
    }
}
