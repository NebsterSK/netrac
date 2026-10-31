<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(asText: true),
            'color' => fake()->hexColor(),
            'position' => 0,
            'tracks_monthly_balance' => false,
        ];
    }

    public function tracksMonthlyBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'tracks_monthly_balance' => true,
        ]);
    }
}
