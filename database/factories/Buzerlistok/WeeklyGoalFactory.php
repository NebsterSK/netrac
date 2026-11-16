<?php

namespace Database\Factories\Buzerlistok;

use App\Models\Buzerlistok\Week;
use App\Models\Buzerlistok\WeeklyGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeeklyGoal>
 */
class WeeklyGoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'week_id' => Week::factory(),
            'name' => $this->faker->words(2, true),
            'target' => $this->faker->numberBetween(1, 5),
            'completions' => 0,
            'position' => 0,
        ];
    }
}
