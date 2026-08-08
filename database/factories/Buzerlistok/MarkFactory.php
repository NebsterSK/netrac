<?php

namespace Database\Factories\Buzerlistok;

use App\Enums\Buzerlistok\MarkStatus;
use App\Models\Buzerlistok\Goal;
use App\Models\Buzerlistok\Mark;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mark>
 */
class MarkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory(),
            'marked_on' => now()->toDateString(),
            'status' => MarkStatus::Success,
            'comment' => null,
        ];
    }
}
