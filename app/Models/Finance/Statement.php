<?php

namespace App\Models\Finance;

use Database\Factories\Finance\StatementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $is_draft
 * @property-read Collection<int, StatementBalance> $balances
 * @property-read int|null $balances_count
 *
 * @method static \Database\Factories\Finance\StatementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereIsDraft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Statement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['date', 'note', 'is_draft'])]
class Statement extends Model
{
    /** @use HasFactory<StatementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_draft' => 'boolean',
        ];
    }

    /**
     * @return HasMany<StatementBalance, $this>
     */
    public function balances(): HasMany
    {
        return $this->hasMany(StatementBalance::class);
    }

    /**
     * The amount the given asset held on this statement; 0 when it has no balance recorded.
     */
    public function balanceOf(int $assetId): int
    {
        return $this->balances->firstWhere('asset_id', $assetId)?->amount ?? 0;
    }
}
