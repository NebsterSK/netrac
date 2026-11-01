<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(asText: true),
            'color' => fake()->hexColor(),
            'tracks_monthly_balance' => false,
        ];
    }

    /**
     * Make the asset the one that drives the monthly balance.
     */
    public function tracksMonthlyBalance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tracks_monthly_balance' => true,
        ]);
    }
}
