<?php

namespace App\Data\Finance;

use App\Models\Finance\Statement;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StatementData extends Data
{
    /**
     * @param  array<int, int>  $balances  amount keyed by asset id
     */
    public function __construct(
        public int $id,
        public Carbon $date,
        public array $balances,
        public ?string $note,
        public bool $is_draft,
    ) {}

    public static function fromModel(Statement $statement): self
    {
        return new self(
            id: $statement->id,
            date: $statement->date,
            balances: $statement->balances->pluck('amount', 'asset_id')->all(),
            note: $statement->note,
            is_draft: $statement->is_draft,
        );
    }
}
