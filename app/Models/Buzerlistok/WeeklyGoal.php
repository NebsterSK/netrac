<?php

namespace App\Models\Buzerlistok;

use Database\Factories\Buzerlistok\WeeklyGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A goal for the whole week, done `target` times on any days.
 *
 * @property int $id
 * @property int $week_id
 * @property string $name
 * @property int $target
 * @property int $completions
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Week $week
 *
 * @method static \Database\Factories\Buzerlistok\WeeklyGoalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyGoal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyGoal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeeklyGoal query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['week_id', 'name', 'target', 'completions', 'position'])]
class WeeklyGoal extends Model
{
    /** @use HasFactory<WeeklyGoalFactory> */
    use HasFactory;

    protected $table = 'buzerlistok_weekly_goals';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target' => 'integer',
            'completions' => 'integer',
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
}
