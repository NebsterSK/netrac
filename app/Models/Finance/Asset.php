<?php

namespace App\Models\Finance;

use Database\Factories\Finance\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A place money is held (bank account, pension fund, broker…); one column of the Net Worth table.
 *
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property int $position
 * @property bool $tracks_monthly_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, StatementBalance> $balances
 * @property-read int|null $balances_count
 *
 * @method static \Database\Factories\Finance\AssetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'color', 'position', 'tracks_monthly_balance'])]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'tracks_monthly_balance' => 'boolean',
        ];
    }

    /**
     * @return HasMany<StatementBalance, $this>
     */
    public function balances(): HasMany
    {
        return $this->hasMany(StatementBalance::class);
    }
}
