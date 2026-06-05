<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Wedding>
 */
class WeddingFactory extends Factory
{
    protected $model = Wedding::class;

    public function definition(): array
    {
        $name = fake()->firstName().' & '.fake()->firstName();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'event_date' => fake()->dateTimeBetween('+2 months', '+18 months')->format('Y-m-d'),
            'timezone' => 'America/Toronto',
            'settings' => [],
        ];
    }
}
