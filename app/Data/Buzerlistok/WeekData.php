<?php

namespace App\Data\Buzerlistok;

use App\Models\Buzerlistok\Goal;
use App\Models\Buzerlistok\Week;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WeekData extends Data
{
    /**
     * @param  array<int, string>  $days
     * @param  array<int, GoalData>  $goals
     */
    public function __construct(
        public int $id,
        public string $starts_on,
        public array $days,
        #[DataCollectionOf(GoalData::class)]
        public array $goals,
    ) {}

    public static function fromWeek(Week $week): self
    {
        $startsOn = $week->starts_on;

        $days = collect(range(0, 6))
            ->map(fn (int $offset): string => $startsOn->copy()->addDays($offset)->toDateString())
            ->all();

        return new self(
            id: $week->id,
            starts_on: $startsOn->toDateString(),
            days: $days,
            goals: $week->goals
                ->map(fn (Goal $goal): GoalData => GoalData::fromGoal($goal))
                ->all(),
        );
    }
}
