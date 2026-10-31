<?php

namespace App\Data\Finance;

use App\Models\Finance\Statement;
use App\Models\Finance\Vault;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A month's balance, derived from how much the vault that tracks the monthly
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
     * Diff each statement's amount in the vault against the statement before
     * it. The earliest statement has nothing to diff against, so it yields no
     * balance. Without a vault there is nothing to track.
     *
     * @param  Collection<int, Statement>  $statements  with their vaults loaded
     * @return Collection<int, self> oldest first
     */
    public static function fromStatements(Collection $statements, ?Vault $vault): Collection
    {
        if ($vault === null) {
            return collect();
        }

        $sorted = $statements->sortBy('date')->values();

        return $sorted
            ->skip(1)
            ->map(fn (Statement $statement, int $index): self => new self(
                date: $statement->date->toDateString(),
                amount: $statement->amountIn($vault) - $sorted[$index - 1]->amountIn($vault),
                note: $statement->note,
                is_draft: $statement->is_draft,
            ))
            ->values();
    }
}
