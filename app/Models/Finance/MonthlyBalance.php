<?php

namespace App\Models\Finance;

use Database\Factories\Finance\MonthlyBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property int $amount
 * @property string|null $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\Finance\MonthlyBalanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyBalance whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyBalance whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyBalance whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MonthlyBalance whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['date', 'amount', 'comment'])]
class MonthlyBalance extends Model
{
    /** @use HasFactory<MonthlyBalanceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'integer',
        ];
    }
}
