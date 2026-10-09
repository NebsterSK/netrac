<?php

namespace App\Data\Finance;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ExpenseSliceData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $color,
        public int $amount,
    ) {}
}
