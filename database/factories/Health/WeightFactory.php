<?php

namespace Database\Factories\Health;

use App\Models\Health\Weight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Weight>
 */
class WeightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'weight' => fake()->randomFloat(1, 60, 95),
        ];
    }
}
