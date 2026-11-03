<?php

namespace App\Data\Finance;

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A month's balance, derived from how much the asset that tracks the monthly
 * balance (the owner's Account) changed since the owner's previous statement.
 * Never stored.
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
     * Build the balances for the given owner filter; a null owner means all
     * owners, whose balances are summed per month. Loads only the tracked
     * assets' amounts instead of every pivot row.
     *
     * @return Collection<int, self> oldest first
     */
    public static function forOwnerFilter(?int $ownerId): Collection
    {
        $trackedAssets = Asset::monthlyBalanceAssets()
            ->when($ownerId !== null, fn (Collection $assets): Collection => $assets->filter(fn (Asset $asset): bool => $asset->user_id === $ownerId));

        if ($trackedAssets->isEmpty()) {
            return collect();
        }

        $statements = Statement::query()
            ->whereIn('user_id', $trackedAssets->keys())
            ->with(['assets' => fn ($query) => $query->whereKey($trackedAssets->pluck('id'))])
            ->get();

        $seriesByOwner = $trackedAssets
            ->sortKeys()
            ->map(fn (Asset $asset, int $userId): Collection => self::fromStatements($statements->where('user_id', $userId), $asset)
                ->keyBy(fn (self $balance): string => substr($balance->date, 0, 7)));

        if ($ownerId !== null) {
            return $seriesByOwner->first()->values();
        }

        $trackedAssets->load('owner');

        return self::combine($seriesByOwner, $trackedAssets->map(fn (Asset $asset): string => $asset->owner->name));
    }

    /**
     * Diff each statement's amount in the asset against the statement before
     * it. The earliest statement has nothing to diff against, so it yields no
     * balance. Without an asset there is nothing to track.
     *
     * @param  Collection<int, Statement>  $statements  one owner's, with their assets loaded
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

    /**
     * Sum every owner's balance per month. An owner is required from their
     * first balance month onwards; a month is a draft while any required owner
     * lacks a balance for it or has a draft one.
     *
     * @param  Collection<int, Collection<string, self>>  $seriesByOwner  each owner's balances keyed by 'YYYY-MM', keyed by owner id
     * @param  Collection<int, string>  $ownerNames  keyed by owner id
     * @return Collection<int, self> oldest first
     */
    private static function combine(Collection $seriesByOwner, Collection $ownerNames): Collection
    {
        $firstMonthByOwner = $seriesByOwner
            ->reject(fn (Collection $series): bool => $series->isEmpty())
            ->map(fn (Collection $series): string => $series->keys()->min());

        return $seriesByOwner
            ->flatMap(fn (Collection $series): array => $series->keys()->all())
            ->unique()
            ->sort()
            ->map(function (string $month) use ($seriesByOwner, $firstMonthByOwner, $ownerNames): self {
                $balances = $seriesByOwner
                    ->map(fn (Collection $series): ?self => $series->get($month))
                    ->filter();

                $requiredOwnerIds = $firstMonthByOwner->filter(fn (string $firstMonth): bool => $firstMonth <= $month)->keys();

                $note = $balances
                    ->filter(fn (self $balance): bool => filled($balance->note))
                    ->map(fn (self $balance, int $ownerId): string => $ownerNames[$ownerId].': '.$balance->note)
                    ->implode(' · ');

                return new self(
                    date: $month.'-01',
                    amount: (int) $balances->sum('amount'),
                    note: $note === '' ? null : $note,
                    is_draft: $requiredOwnerIds->contains(
                        fn (int $ownerId): bool => ! $balances->has($ownerId) || $balances[$ownerId]->is_draft,
                    ),
                );
            })
            ->values();
    }
}
