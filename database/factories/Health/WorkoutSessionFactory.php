<?php

namespace Database\Factories\Health;

use App\Models\Health\WorkoutSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutSession>
 */
class WorkoutSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'performed_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
