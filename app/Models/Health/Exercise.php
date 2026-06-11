<?php

namespace App\Models\Health;

use App\Enums\Health\MovementPattern;
use Database\Factories\Health\ExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exercise_category_id
 * @property string $name
 * @property MovementPattern|null $movement_pattern
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ExerciseCategory $exerciseCategory
 *
 * @method static \Database\Factories\Health\ExerciseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exercise newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exercise newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exercise query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exercise whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exercise whereExerciseCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exercise whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exercise whereMovementPattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exercise whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exercise whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['exercise_category_id', 'name', 'movement_pattern'])]
class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_pattern' => MovementPattern::class,
        ];
    }

    /**
     * @return BelongsTo<ExerciseCategory, $this>
     */
    public function exerciseCategory(): BelongsTo
    {
        return $this->belongsTo(ExerciseCategory::class);
    }
}
