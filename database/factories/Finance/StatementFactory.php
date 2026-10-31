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
     * Record the given amount in the asset once the statement is created.
     */
    public function withAmount(Asset $asset, int $amount): static
    {
        return $this->afterCreating(function (Statement $statement) use ($asset, $amount): void {
            $statement->assets()->attach($asset->id, ['amount' => $amount]);
        });
    }
}
