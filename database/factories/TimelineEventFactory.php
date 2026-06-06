<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\TimelineEvent;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimelineEvent>
 */
class TimelineEventFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+6 months');
        $end = (clone $start)->modify('+'.fake()->numberBetween(30, 180).' minutes');

        return [
            'wedding_id' => Wedding::factory(),
            'vendor_id' => null,
            'title' => fake()->sentence(3),
            'type' => fake()->randomElement(EventType::cases()),
            'starts_at' => $start,
            'ends_at' => fake()->boolean() ? $end : null,
            'location' => fake()->optional()->streetName(),
            'notes' => null,
        ];
    }
}
