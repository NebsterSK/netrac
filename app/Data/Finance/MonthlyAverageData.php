<?php

namespace App\Data\Finance;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MonthlyAverageData extends Data
{
    public function __construct(
        public int $month,
        public int $average,
        public int $count,
    ) {}
}
