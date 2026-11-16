<?php

namespace App\Data\Buzerlistok;

use App\Models\Buzerlistok\WeeklyGoal;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WeeklyGoalData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $target,
        public int $completions,
    ) {}

    public static function fromWeeklyGoal(WeeklyGoal $weeklyGoal): self
    {
        return new self(
            id: $weeklyGoal->id,
            name: $weeklyGoal->name,
            target: $weeklyGoal->target,
            completions: $weeklyGoal->completions,
        );
    }
}
