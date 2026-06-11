<?php

namespace Database\Seeders\Health;

use App\Enums\Health\ExerciseCategory as ExerciseCategoryEnum;
use App\Enums\Health\MovementPattern;
use App\Models\Health\Exercise;
use App\Models\Health\ExerciseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        /** @var array<string, list<array{0: string, 1: MovementPattern|null}>> $exercisesByCategory */
        $exercisesByCategory = [
            ExerciseCategoryEnum::Chest->value => [
                ['Bench Press', MovementPattern::Push],
                ['Incline Dumbbell Press', MovementPattern::Push],
                ['Cable Fly', MovementPattern::Push],
            ],
            ExerciseCategoryEnum::Shoulders->value => [
                ['Overhead Press', MovementPattern::Push],
                ['Dumbbell Shoulder Press', MovementPattern::Push],
                ['Lateral Raise', MovementPattern::Push],
            ],
            ExerciseCategoryEnum::Legs->value => [
                ['Back Squat', MovementPattern::Squat],
                ['Leg Press', MovementPattern::Squat],
                ['Romanian Deadlift', MovementPattern::Hinge],
            ],
            ExerciseCategoryEnum::Back->value => [
                ['Deadlift', MovementPattern::Pull],
                ['Pull Up', MovementPattern::Pull],
                ['Barbell Row', MovementPattern::Pull],
            ],
            ExerciseCategoryEnum::Core->value => [
                ['Plank', null],
                ['Hanging Leg Raise', null],
                ['Cable Crunch', null],
            ],
            ExerciseCategoryEnum::Cardio->value => [
                ['Running', null],
                ['Cycling', null],
                ['Rowing', null],
            ],
            ExerciseCategoryEnum::Arms->value => [
                ['Tricep Pushdown', MovementPattern::Push],
                ['Bicep Curl', MovementPattern::Pull],
                ['Hammer Curl', MovementPattern::Pull],
            ],
        ];

        foreach ($exercisesByCategory as $categoryName => $exercises) {
            $category = ExerciseCategory::where('name', $categoryName)->firstOrFail();

            foreach ($exercises as [$exerciseName, $movementPattern]) {
                Exercise::factory()->create([
                    'exercise_category_id' => $category->id,
                    'name' => $exerciseName,
                    'movement_pattern' => $movementPattern,
                ]);
            }
        }
    }
}
