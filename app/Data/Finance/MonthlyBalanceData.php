<?php

namespace App\Data\Finance;

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A month's balance, derived from how much the asset that tracks the monthly
 * balance (the Account) changed since the previous statement. Never stored.
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
     * Build the balances from every statement, loading only the tracked
     * asset's amounts instead of every pivot row.
     *
     * @return Collection<int, self> oldest first
     */
    public static function fromAllStatements(): Collection
    {
        $asset = Asset::monthlyBalanceAsset();

        if ($asset === null) {
            return collect();
        }

        $statements = Statement::with(['assets' => fn ($query) => $query->whereKey($asset->id)])->get();

        return self::fromStatements($statements, $asset);
    }

    /**
     * Diff each statement's amount in the asset against the statement before
     * it. The earliest statement has nothing to diff against, so it yields no
     * balance. Without an asset there is nothing to track.
     *
     * @param  Collection<int, Statement>  $statements  with their assets loaded
     * @return Collection<int, self> oldest first
     */
    public static function fromStatements(Collection $statements, ?Asset $asset): Collection
    {
        if ($asset === null) {
            return collect();
        }

        $sorted = $statements->sortBy('date')->values();

        return $sorted
            ->skip(1)
            ->map(fn (Statement $statement, int $index): self => new self(
                date: $statement->date->toDateString(),
                amount: $statement->amountIn($asset) - $sorted[$index - 1]->amountIn($asset),
                note: $statement->note,
                is_draft: $statement->is_draft,
            ))
            ->values();
    }
}
