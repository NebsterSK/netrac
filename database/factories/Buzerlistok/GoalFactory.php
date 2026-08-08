<?php

namespace Database\Factories\Buzerlistok;

use App\Models\Buzerlistok\Goal;
use App\Models\Buzerlistok\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'week_id' => Week::factory(),
            'name' => $this->faker->words(2, true),
            'position' => 0,
        ];
    }
}
