<?php

namespace App\Data\Health;

use App\Models\Health\ExerciseWorkoutSession;
use App\Models\Health\WorkoutSession;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SessionListItemData extends Data
{
    public function __construct(
        public int $id,
        public Carbon $performed_at,
        public int $total,
        public int $completed,
    ) {}

    public static function fromSession(WorkoutSession $session): self
    {
        return new self(
            id: $session->id,
            performed_at: $session->performed_at,
            total: $session->exerciseEntries->count(),
            completed: $session->exerciseEntries
                ->filter(fn (ExerciseWorkoutSession $entry): bool => $entry->completed)
                ->count(),
        );
    }
}
