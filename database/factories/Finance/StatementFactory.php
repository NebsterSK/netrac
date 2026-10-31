<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
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
            'is_draft' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_draft' => true,
        ]);
    }

    /**
     * Record how much the given asset held on the statement.
     */
    public function withBalance(Asset $asset, int $amount): static
    {
        return $this->afterCreating(fn (Statement $statement) => $statement->balances()->create([
            'asset_id' => $asset->id,
            'amount' => $amount,
        ]));
    }
}
