<?php

namespace App\Models\Finance;

use App\Models\Concerns\HasOwner;
use Database\Factories\Finance\StatementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property-read string $month 'YYYY-MM', generated from the date
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $is_draft
 * @property-read Collection<int, Asset> $assets
 * @property-read int|null $assets_count
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
#[Fillable(['user_id', 'date', 'note', 'is_draft'])]
class Statement extends Model
{
    /** @use HasFactory<StatementFactory> */
    use HasFactory, HasOwner;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_draft' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Asset, $this, AssetStatement>
     */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class)->using(AssetStatement::class)->withPivot('amount')->withTimestamps();
    }

    /**
     * Amount held in each asset, keyed by asset id.
     *
     * @return array<int, int>
     */
    public function amounts(): array
    {
        return $this->assets
            ->mapWithKeys(fn (Asset $asset): array => [$asset->id => (int) $asset->pivot->amount])
            ->all();
    }

    /**
     * Amount held in the given asset; an asset without a recorded amount holds nothing.
     */
    public function amountIn(Asset $asset): int
    {
        return (int) $this->assets->firstWhere('id', $asset->id)?->pivot->amount;
    }
}
