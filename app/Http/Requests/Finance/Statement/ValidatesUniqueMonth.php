<?php

namespace App\Http\Requests\Finance\Statement;

use App\Models\Finance\Statement;
use Closure;
use Illuminate\Support\Carbon;

/**
 * An owner has at most one statement per month.
 */
trait ValidatesUniqueMonth
{
    abstract protected function statementOwnerId(): ?int;

    /**
     * Fails when the owner already has another statement in the date's month.
     */
    protected function uniqueMonthRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $ownerId = $this->statementOwnerId();

            if ($ownerId === null) {
                return;
            }

            $currentStatement = $this->route('statement');

            $monthTaken = Statement::query()
                ->ownedBy($ownerId)
                ->where('month', Carbon::parse($value)->format('Y-m'))
                ->when($currentStatement instanceof Statement, fn ($query) => $query->whereKeyNot($currentStatement->getKey()))
                ->exists();

            if ($monthTaken) {
                $fail('This owner already has a statement for this month.');
            }
        };
    }
}
