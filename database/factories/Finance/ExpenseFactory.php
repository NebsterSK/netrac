<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Expense;
use App\Models\Finance\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'name' => fake()->unique()->words(asText: true),
            'amount' => fake()->numberBetween(5, 2000),
        ];
    }
}
