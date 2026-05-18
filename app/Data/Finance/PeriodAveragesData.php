<?php

namespace App\Data\Finance;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PeriodAveragesData extends Data
{
    public function __construct(
        public ?int $last6,
        public ?int $last12,
        public ?int $last18,
        public ?int $overall,
    ) {}
}
