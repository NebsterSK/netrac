<?php

namespace App\Data\Health;

use App\Models\Health\Weight;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class WeightData extends Data
{
    public function __construct(
        public int $id,
        public string $date,
        public float $weight,
    ) {}

    public static function fromWeight(Weight $weight): self
    {
        return new self(
            id: $weight->id,
            date: $weight->date->toDateString(),
            weight: (float) $weight->weight,
        );
    }
}
