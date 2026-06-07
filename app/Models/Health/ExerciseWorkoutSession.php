<?php

namespace App\Models\Health;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workout_session_id
 * @property int $exercise_id
 * @property int $position
 * @property bool $completed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exercise $exercise
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession whereCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession whereExerciseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseWorkoutSession whereWorkoutSessionId($value)
 *
 * @mixin \Eloquent
 */
class ExerciseWorkoutSession extends Pivot
{
    protected $table = 'exercise_workout_session';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'completed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Exercise, $this>
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
