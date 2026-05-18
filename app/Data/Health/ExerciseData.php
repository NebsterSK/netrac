<?php

namespace App\Data\Health;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ExerciseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $category_id,
        public CategoryData $category,
    ) {}
}
