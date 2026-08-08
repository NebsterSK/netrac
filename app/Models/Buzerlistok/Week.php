<?php

namespace App\Models\Buzerlistok;

use Database\Factories\Buzerlistok\WeekFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $starts_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Goal> $goals
 * @property-read int|null $goals_count
 *
 * @method static \Database\Factories\Buzerlistok\WeekFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Week newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Week newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Week query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['starts_on'])]
class Week extends Model
{
    /** @use HasFactory<WeekFactory> */
    use HasFactory;

    protected $table = 'buzerlistok_weeks';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
        ];
    }

    /**
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class)->orderBy('position');
    }
}
