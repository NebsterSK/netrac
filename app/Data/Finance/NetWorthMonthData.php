<?php

namespace App\Data\Finance;

use App\Models\Finance\Statement;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One Net Worth month: every owner's statement for that month combined into a
 * single row. Under a single-owner filter it holds exactly one statement.
 * Gain is computed here, against the previous combined month, and only over
 * owners with a statement in both months, so an owner joining or returning
 * after a skipped month doesn't count their whole net worth as gain.
 */
#[TypeScript]
class NetWorthMonthData extends Data
{
    /**
     * @param  string  $month  'YYYY-MM'
     * @param  array<int, StatementData>  $statements  the month's statements, ordered by owner id
     * @param  array<int, int>  $amounts  amounts merged across the statements, keyed by asset id (each asset has a single owner)
     * @param  int  $total  sum of the merged amounts
     * @param  int|null  $gain  sum, over owners with a statement in both this and the previous month, of their total change; null for the oldest month or when no owner is in both
     * @param  float|null  $gain_percent  gain relative to those owners' previous totals, in percent; null when gain is null or that base is 0
     * @param  bool  $is_draft  any of the month's statements is a draft
     * @param  bool  $is_complete  every required owner has a statement this month; an owner is required from the month of their first statement onwards
     */
    public function __construct(
        public string $month,
        #[DataCollectionOf(StatementData::class)]
        public array $statements,
        #[LiteralTypeScriptType('Record<number, number>')]
        public array $amounts,
        public int $total,
        public ?int $gain,
        public ?float $gain_percent,
        public bool $is_draft,
        public bool $is_complete,
    ) {}

    /**
     * Build the months for the given owner filter; a null owner means all owners.
     *
     * @return Collection<int, self> newest first
     */
    public static function forOwnerFilter(?int $ownerId): Collection
    {
        $statements = Statement::query()
            ->ownedBy($ownerId)
            ->with('assets')
            ->orderBy('date')
            ->orderBy('user_id')
            ->get();

        return self::fromStatements($statements);
    }

    /**
     * @param  Collection<int, Statement>  $statements  with their assets loaded, oldest first
     * @return Collection<int, self> newest first
     */
    public static function fromStatements(Collection $statements): Collection
    {
        $firstMonthByOwner = $statements
            ->groupBy('user_id')
            ->map(fn (Collection $ownerStatements): string => $ownerStatements->min('month'));

        /** @var array<int, int>|null $previousOwnerTotals */
        $previousOwnerTotals = null;
        $months = collect();

        foreach ($statements->groupBy('month') as $month => $monthStatements) {
            $amounts = $monthStatements->reduce(
                fn (array $merged, Statement $statement): array => $merged + $statement->amounts(),
                [],
            );

            $total = (int) array_sum($amounts);

            $ownerTotals = $monthStatements
                ->mapWithKeys(fn (Statement $statement): array => [$statement->user_id => (int) array_sum($statement->amounts())])
                ->all();

            [$gain, $gainPercent] = self::gainAgainst($ownerTotals, $previousOwnerTotals);

            $requiredOwnerIds = $firstMonthByOwner->filter(fn (string $firstMonth): bool => $firstMonth <= $month)->keys();

            $months->push(new self(
                month: (string) $month,
                statements: StatementData::collect($monthStatements->values()->all()),
                amounts: $amounts,
                total: $total,
                gain: $gain,
                gain_percent: $gainPercent,
                is_draft: $monthStatements->contains('is_draft', true),
                is_complete: $requiredOwnerIds->diff($monthStatements->pluck('user_id'))->isEmpty(),
            ));

            $previousOwnerTotals = $ownerTotals;
        }

        return $months->reverse()->values();
    }

    /**
     * Gain and gain percent over the owners present in both months.
     *
     * @param  array<int, int>  $ownerTotals  this month's totals, keyed by owner id
     * @param  array<int, int>|null  $previousOwnerTotals  the previous month's totals, keyed by owner id; null for the oldest month
     * @return array{0: int|null, 1: float|null}
     */
    private static function gainAgainst(array $ownerTotals, ?array $previousOwnerTotals): array
    {
        if ($previousOwnerTotals === null) {
            return [null, null];
        }

        $commonOwnerIds = array_keys(array_intersect_key($ownerTotals, $previousOwnerTotals));

        if ($commonOwnerIds === []) {
            return [null, null];
        }

        $gain = 0;
        $base = 0;

        foreach ($commonOwnerIds as $ownerId) {
            $gain += $ownerTotals[$ownerId] - $previousOwnerTotals[$ownerId];
            $base += $previousOwnerTotals[$ownerId];
        }

        return [$gain, $base === 0 ? null : (float) ($gain * 100 / $base)];
    }
}
