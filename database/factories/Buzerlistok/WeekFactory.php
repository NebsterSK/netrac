<?php

namespace Database\Factories\Buzerlistok;

use App\Models\Buzerlistok\Week;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Week>
 */
class WeekFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'starts_on' => now()->startOfWeek()->toDateString(),
        ];
    }
}
