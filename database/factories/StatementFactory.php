<?php

namespace Database\Factories;

use App\Models\Statement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Statement>
 */
class StatementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->unique()->date(),
            'account' => fake()->numberBetween(0, 10000),
            'legacy_upgrade' => fake()->numberBetween(0, 10000),
            'uniqua_sds' => fake()->numberBetween(0, 10000),
            'uniqua_dds' => fake()->numberBetween(0, 10000),
            'finax' => fake()->numberBetween(0, 10000),
            'trading212' => fake()->numberBetween(0, 10000),
        ];
    }
}
