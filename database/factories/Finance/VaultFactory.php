<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vault>
 */
class VaultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(asText: true),
            'color' => fake()->hexColor(),
            'position' => fake()->numberBetween(100, 1000),
            'tracks_monthly_balance' => false,
        ];
    }
}
