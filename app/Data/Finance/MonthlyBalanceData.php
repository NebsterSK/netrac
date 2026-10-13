<?php

namespace App\Data\Finance;

use App\Models\Finance\Statement;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A month's balance, derived from how much the Account changed since the
 * previous statement. Never stored.
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
     * Diff each statement's Account against the statement before it. The
     * earliest statement has nothing to diff against, so it yields no balance.
     *
     * @param  Collection<int, Statement>  $statements
     * @return Collection<int, self> oldest first
     */
    public static function fromStatements(Collection $statements): Collection
    {
        $sorted = $statements->sortBy('date')->values();

        return $sorted
            ->skip(1)
            ->map(fn (Statement $statement, int $index): self => new self(
                date: $statement->date->toDateString(),
                amount: $statement->account - $sorted[$index - 1]->account,
                note: $statement->note,
                is_draft: $statement->is_draft,
            ))
            ->values();
    }
}
