<?php

namespace App\Data\Finance;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MonthlyBalanceData extends Data
{
    public function __construct(
        public int $id,
        public Carbon $date,
        public int $amount,
        public ?string $comment,
    ) {}
}
