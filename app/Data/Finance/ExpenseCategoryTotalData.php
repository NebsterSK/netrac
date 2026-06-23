<?php

namespace App\Data\Finance;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ExpenseCategoryTotalData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $total,
        public int $count,
    ) {}
}
