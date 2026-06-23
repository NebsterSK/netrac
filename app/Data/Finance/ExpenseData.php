<?php

namespace App\Data\Finance;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ExpenseData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $expense_category_id,
        public int $amount,
        public ExpenseCategoryData $expenseCategory,
        public Carbon $created_at,
        public Carbon $updated_at,
    ) {}
}
