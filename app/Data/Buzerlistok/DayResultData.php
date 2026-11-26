<?php

namespace App\Data\Buzerlistok;

use App\Enums\Buzerlistok\DayResult;
use App\Enums\Buzerlistok\MarkStatus;
use App\Models\Buzerlistok\Goal;
use App\Models\Buzerlistok\Mark;
use App\Models\Buzerlistok\Week;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One day of a week's daily goals, the raw material for green-day streaks.
 */
#[TypeScript]
class DayResultData extends Data
{
    public function __construct(
        public string $date,
        public DayResult $result,
    ) {}

    /**
     * Every day of every week that has daily goals, oldest first. A day fails
     * as soon as one goal failed, succeeds once every goal is marked otherwise,
     * and is incomplete until then.
     *
     * @return array<int, self>
     */
    public static function forAllWeeks(): array
    {
        return Week::query()
            ->has('goals')
            ->with('goals.marks')
            ->orderBy('starts_on')
            ->get()
            ->flatMap(fn (Week $week): array => collect(range(0, 6))
                ->map(function (int $offset) use ($week): self {
                    $date = $week->starts_on->copy()->addDays($offset)->toDateString();

                    $statuses = $week->goals->map(
                        fn (Goal $goal): ?MarkStatus => $goal->marks
                            ->first(fn (Mark $mark): bool => $mark->marked_on->toDateString() === $date)
                            ?->status,
                    );

                    $result = match (true) {
                        $statuses->contains(MarkStatus::Fail) => DayResult::Fail,
                        $statuses->contains(null) => DayResult::Incomplete,
                        default => DayResult::Success,
                    };

                    return new self(date: $date, result: $result);
                })
                ->all())
            ->values()
            ->all();
    }
}
