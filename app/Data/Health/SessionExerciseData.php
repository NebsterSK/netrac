<?php

namespace App\Data\Health;

use App\Models\Health\ExerciseWorkoutSession;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SessionExerciseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $completed,
        public ExerciseCategoryData $exerciseCategory,
    ) {}

    public static function fromEntry(ExerciseWorkoutSession $entry): self
    {
        return new self(
            id: $entry->exercise->id,
            name: $entry->exercise->name,
            completed: $entry->completed,
            exerciseCategory: ExerciseCategoryData::from($entry->exercise->exerciseCategory),
        );
    }
}
