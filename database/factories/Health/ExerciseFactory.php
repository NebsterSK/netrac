<?php

namespace Database\Factories\Health;

use App\Models\Health\Exercise;
use App\Models\Health\ExerciseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exercise_category_id' => ExerciseCategory::factory(),
            'name' => fake()->unique()->words(asText: true),
        ];
    }
}
