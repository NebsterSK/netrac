<?php

namespace App\Data\Finance;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AssetData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $color,
        public bool $tracks_monthly_balance,
        public ?int $statements_count = null,
    ) {}
}
