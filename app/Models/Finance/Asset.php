<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasOwner;
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
#[Fillable(['user_id', 'name', 'color', 'tracks_monthly_balance'])]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory, HasOwner;

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
     * The assets whose month-to-month change is the monthly balance, keyed by owner id.
     *
     * @return Collection<int, self>
     */
    public static function monthlyBalanceAssets(): Collection
    {
        /** @var Collection<int, self> */
        return self::query()->where('tracks_monthly_balance', true)->orderBy('id')->get()->keyBy('user_id');
    }
}
