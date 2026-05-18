<?php

namespace App\Data\Health;

use App\Models\Health\ExerciseWorkoutSession;
use App\Models\Health\WorkoutSession;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SessionDetailData extends Data
{
    /**
     * @param  array<int, SessionExerciseData>  $exercises
     */
    public function __construct(
        public int $id,
        public Carbon $performed_at,
        #[DataCollectionOf(SessionExerciseData::class)]
        public array $exercises,
    ) {}

    public static function fromSession(WorkoutSession $session): self
    {
        return new self(
            id: $session->id,
            performed_at: $session->performed_at,
            exercises: $session->exerciseEntries
                ->map(fn (ExerciseWorkoutSession $entry): SessionExerciseData => SessionExerciseData::fromEntry($entry))
                ->all(),
        );
    }
}
