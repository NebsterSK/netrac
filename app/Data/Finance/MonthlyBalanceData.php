<?php

namespace App\Data\Finance;

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A month's balance, derived from how much the asset that tracks the monthly
 * balance changed since the previous statement. Never stored.
 */
#[TypeScript]
class MonthlyBalanceData extends Data
{
    public function __construct(
        public string $date,
        public int $amount,
        public ?string $note,
        public bool $is_draft,
    ) {}

    /**
     * Diff each statement's tracked asset amount against the statement before
     * it. The earliest statement has nothing to diff against, so it yields no
     * balance; without a tracked asset there are no balances at all.
     *
     * @param  Collection<int, Statement>  $statements  with balances loaded
     * @return Collection<int, self> oldest first
     */
    public static function fromStatements(Collection $statements, ?Asset $trackedAsset): Collection
    {
        if ($trackedAsset === null) {
            return collect();
        }

        $sorted = $statements->sortBy('date')->values();

        return $sorted
            ->skip(1)
            ->map(fn (Statement $statement, int $index): self => new self(
                date: $statement->date->toDateString(),
                amount: $statement->balanceOf($trackedAsset->id) - $sorted[$index - 1]->balanceOf($trackedAsset->id),
                note: $statement->note,
                is_draft: $statement->is_draft,
            ))
            ->values();
    }
}
