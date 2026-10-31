<?php

namespace App\Models\Finance;

use Database\Factories\Finance\StatementBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * How much one asset held at the time of one statement.
 *
 * @property int $id
 * @property int $statement_id
 * @property int $asset_id
 * @property int $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Asset $asset
 * @property-read Statement $statement
 *
 * @method static \Database\Factories\Finance\StatementBalanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatementBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatementBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatementBalance query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['statement_id', 'asset_id', 'amount'])]
class StatementBalance extends Model
{
    /** @use HasFactory<StatementBalanceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Statement, $this>
     */
    public function statement(): BelongsTo
    {
        return $this->belongsTo(Statement::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
