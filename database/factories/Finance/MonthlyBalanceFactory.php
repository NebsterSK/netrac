<?php

namespace Database\Factories\Finance;

use App\Models\Finance\MonthlyBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyBalance>
 */
class MonthlyBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->date(),
            'amount' => fake()->numberBetween(-500, 2000),
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
