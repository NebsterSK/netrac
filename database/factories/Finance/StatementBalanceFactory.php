<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use App\Models\Finance\StatementBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatementBalance>
 */
class StatementBalanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'statement_id' => Statement::factory(),
            'asset_id' => Asset::factory(),
            'amount' => fake()->numberBetween(0, 10000),
        ];
    }
}
