<?php

namespace Database\Seeders\Health;

use App\Models\Health\Category;
use App\Models\Health\Exercise;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $exercisesByCategory = [
            'Chest' => ['Bench Press', 'Incline Dumbbell Press', 'Push Up', 'Cable Fly'],
            'Legs' => ['Back Squat', 'Romanian Deadlift', 'Leg Press', 'Walking Lunge'],
            'Back' => ['Deadlift', 'Pull Up', 'Barbell Row', 'Lat Pulldown'],
            'Core' => ['Plank', 'Hanging Leg Raise', 'Cable Crunch', 'Russian Twist'],
            'Cardio' => ['Running', 'Cycling', 'Jump Rope', 'Rowing'],
        ];

        foreach ($exercisesByCategory as $categoryName => $exerciseNames) {
            $category = Category::factory()->create(['name' => $categoryName]);

            foreach ($exerciseNames as $exerciseName) {
                Exercise::factory()->create([
                    'category_id' => $category->id,
                    'name' => $exerciseName,
                ]);
            }
        }
    }
}
