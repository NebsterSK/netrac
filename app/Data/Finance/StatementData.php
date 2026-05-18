<?php

namespace App\Data\Finance;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StatementData extends Data
{
    public function __construct(
        public int $id,
        public Carbon $date,
        public int $account,
        public int $legacy_upgrade,
        public int $uniqa_sds,
        public int $uniqa_dds,
        public int $finax,
        public int $trading212,
    ) {}
}
