<?php

namespace Database\Seeders\Health;

use App\Enums\Health\ExerciseCategory as ExerciseCategoryEnum;
use App\Models\Health\Exercise;
use App\Models\Health\ExerciseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $exercisesByCategory = [
            ExerciseCategoryEnum::Chest->value => ['Bench Press', 'Incline Dumbbell Press', 'Cable Fly'],
            ExerciseCategoryEnum::Legs->value => ['Back Squat', 'Romanian Deadlift', 'Leg Press'],
            ExerciseCategoryEnum::Back->value => ['Deadlift', 'Pull Up', 'Barbell Row'],
            ExerciseCategoryEnum::Core->value => ['Plank', 'Hanging Leg Raise', 'Cable Crunch'],
            ExerciseCategoryEnum::Cardio->value => ['Running', 'Cycling', 'Rowing'],
            ExerciseCategoryEnum::Arms->value => ['Bicep Curl', 'Tricep Pushdown', 'Hammer Curl'],
        ];

        foreach ($exercisesByCategory as $categoryName => $exerciseNames) {
            $category = ExerciseCategory::where('name', $categoryName)->firstOrFail();

            foreach ($exerciseNames as $exerciseName) {
                Exercise::factory()->create([
                    'exercise_category_id' => $category->id,
                    'name' => $exerciseName,
                ]);
            }
        }
    }
}
