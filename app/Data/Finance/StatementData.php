<?php

namespace App\Data\Finance;

use App\Models\Finance\Statement;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StatementData extends Data
{
    /**
     * @param  array<int, int>  $amounts  amount held in each asset, keyed by asset id
     */
    public function __construct(
        public int $id,
        public Carbon $date,
        #[LiteralTypeScriptType('Record<number, number>')]
        public array $amounts,
        public ?string $note,
        public bool $is_draft,
    ) {}

    public static function fromModel(Statement $statement): self
    {
        return new self(
            id: $statement->id,
            date: $statement->date,
            amounts: $statement->amounts(),
            note: $statement->note,
            is_draft: $statement->is_draft,
        );
    }
}
