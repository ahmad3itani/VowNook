<?php

namespace Database\Factories;

use App\Enums\CrewRole;
use App\Models\CrewMember;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrewMember>
 */
class CrewMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wedding_id' => Wedding::factory(),
            'name' => fake()->name(),
            'role' => fake()->randomElement(CrewRole::cases()),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
