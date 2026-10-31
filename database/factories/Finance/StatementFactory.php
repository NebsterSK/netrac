<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Statement;
use App\Models\Finance\Vault;
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
     * Record the given amount in the vault once the statement is created.
     */
    public function withAmount(Vault $vault, int $amount): static
    {
        return $this->afterCreating(function (Statement $statement) use ($vault, $amount): void {
            $statement->vaults()->attach($vault->id, ['amount' => $amount]);
        });
    }
}
