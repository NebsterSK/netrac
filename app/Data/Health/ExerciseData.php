<?php

namespace App\Data\Health;

use App\Enums\Health\MovementPattern;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ExerciseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $exercise_category_id,
        public ?MovementPattern $movement_pattern,
        public ExerciseCategoryData $exerciseCategory,
        public Carbon $created_at,
        public Carbon $updated_at,
    ) {}
}
