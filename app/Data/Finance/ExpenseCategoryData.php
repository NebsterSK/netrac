<?php

namespace App\Data\Finance;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ExpenseCategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $color = null,
        public ?int $expenses_count = null,
    ) {}
}
