<?php

namespace App\Models\Buzerlistok;

use App\Enums\Buzerlistok\MarkStatus;
use Database\Factories\Buzerlistok\MarkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $goal_id
 * @property Carbon $marked_on
 * @property MarkStatus $status
 * @property string|null $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Goal $goal
 *
 * @method static \Database\Factories\Buzerlistok\MarkFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark whereGoalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark whereMarkedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mark whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['goal_id', 'marked_on', 'status', 'comment'])]
class Mark extends Model
{
    /** @use HasFactory<MarkFactory> */
    use HasFactory;

    protected $table = 'buzerlistok_marks';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marked_on' => 'date',
            'status' => MarkStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
