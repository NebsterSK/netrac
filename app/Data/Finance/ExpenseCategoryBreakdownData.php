<?php

namespace App\Data\Finance;

use App\Models\Finance\Expense;
use App\Models\Finance\ExpenseCategory;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ExpenseCategoryBreakdownData extends Data
{
    /**
     * @param  array<int, ExpenseSliceData>  $expenses
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $color,
        public int $total,
        #[DataCollectionOf(ExpenseSliceData::class)]
        public array $expenses,
    ) {}

    public static function fromCategory(ExpenseCategory $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            color: $category->color,
            total: (int) $category->expenses->sum('amount'),
            expenses: $category->expenses
                ->map(fn (Expense $expense): ExpenseSliceData => ExpenseSliceData::from($expense))
                ->all(),
        );
    }
}
