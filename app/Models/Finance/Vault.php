<?php

namespace App\Models\Finance;

use Database\Factories\Finance\VaultFactory;
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
 * @property int $position
 * @property bool $tracks_monthly_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Statement> $statements
 * @property-read int|null $statements_count
 *
 * @method static \Database\Factories\Finance\VaultFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vault newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vault newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vault query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'color', 'position', 'tracks_monthly_balance'])]
class Vault extends Model
{
    /** @use HasFactory<VaultFactory> */
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
     * @return BelongsToMany<Statement, $this>
     */
    public function statements(): BelongsToMany
    {
        return $this->belongsToMany(Statement::class)->withPivot('amount');
    }

    /**
     * The vault whose month-to-month change is the monthly balance, if any.
     */
    public static function monthlyBalanceVault(): ?self
    {
        return self::query()->where('tracks_monthly_balance', true)->first();
    }
}
