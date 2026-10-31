<?php

namespace App\Models\Finance;

use Database\Factories\Finance\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A place money is kept (bank account, pension fund, broker), tracked as a
 * column on the Net Worth page.
 *
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property bool $tracks_monthly_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Statement> $statements
 * @property-read int|null $statements_count
 * @property-read AssetStatement|null $pivot
 *
 * @method static \Database\Factories\Finance\AssetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'color', 'tracks_monthly_balance'])]
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
            'tracks_monthly_balance' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Statement, $this, AssetStatement>
     */
    public function statements(): BelongsToMany
    {
        return $this->belongsToMany(Statement::class)->using(AssetStatement::class)->withPivot('amount')->withTimestamps();
    }

    /**
     * The asset whose month-to-month change is the monthly balance, if any.
     */
    public static function monthlyBalanceAsset(): ?self
    {
        return self::query()->where('tracks_monthly_balance', true)->orderBy('id')->first();
    }
}
