<?php

namespace App\Models\Finance;

use Database\Factories\Finance\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Expense> $expenses
 * @property-read int|null $expenses_count
 *
 * @method static \Database\Factories\Finance\ExpenseCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name'])]
class ExpenseCategory extends Model
{
    /** @use HasFactory<ExpenseCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
