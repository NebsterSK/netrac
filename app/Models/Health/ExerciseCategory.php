<?php

namespace App\Models\Health;

use Database\Factories\Health\ExerciseCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Exercise> $exercises
 * @property-read int|null $exercises_count
 *
 * @method static \Database\Factories\Health\ExerciseCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseCategory wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExerciseCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'priority'])]
class ExerciseCategory extends Model
{
    /** @use HasFactory<ExerciseCategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => 'integer',
        ];
    }

    /**
     * @return HasMany<Exercise, $this>
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }
}
