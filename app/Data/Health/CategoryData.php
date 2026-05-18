<?php

namespace App\Data\Health;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
