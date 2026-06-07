<?php

namespace App\Data\Health;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ExerciseCategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $priority,
    ) {}
}
