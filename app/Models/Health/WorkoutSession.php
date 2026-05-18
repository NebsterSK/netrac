<?php

namespace App\Models\Health;

use Database\Factories\Health\WorkoutSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $performed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, \App\Models\Health\ExerciseWorkoutSession> $exerciseEntries
 * @property-read int|null $exercise_entries_count
 * @property-read \App\Models\Health\ExerciseWorkoutSession|null $pivot
 * @property-read Collection<int, \App\Models\Health\Exercise> $exercises
 * @property-read int|null $exercises_count
 * @method static \Database\Factories\Health\WorkoutSessionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutSession wherePerformedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkoutSession whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable(['performed_at'])]
class WorkoutSession extends Model
{
    /** @use HasFactory<WorkoutSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Exercise, $this, ExerciseWorkoutSession>
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class)
            ->using(ExerciseWorkoutSession::class)
            ->withPivot(['position', 'completed'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * @return HasMany<ExerciseWorkoutSession, $this>
     */
    public function exerciseEntries(): HasMany
    {
        return $this->hasMany(ExerciseWorkoutSession::class)->orderBy('position');
    }
}
