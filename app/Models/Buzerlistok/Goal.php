<?php

namespace App\Models\Buzerlistok;

use Database\Factories\Buzerlistok\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $week_id
 * @property string $name
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Week $week
 * @property-read Collection<int, Mark> $marks
 * @property-read int|null $marks_count
 *
 * @method static \Database\Factories\Buzerlistok\GoalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goal query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['week_id', 'name', 'position'])]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    protected $table = 'buzerlistok_goals';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Week, $this>
     */
    public function week(): BelongsTo
    {
        return $this->belongsTo(Week::class);
    }

    /**
     * @return HasMany<Mark, $this>
     */
    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}
