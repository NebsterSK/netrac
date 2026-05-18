<?php

namespace Database\Seeders\Health;

use App\Models\Health\Exercise;
use App\Models\Health\WorkoutSession;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkoutSessionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $exercises = Exercise::all();

        if ($exercises->isEmpty()) {
            return;
        }

        WorkoutSession::factory()
            ->count(5)
            ->create()
            ->each(function (WorkoutSession $session) use ($exercises): void {
                $picked = $exercises->random(min(6, $exercises->count()));

                $pivot = [];
                foreach ($picked->values() as $position => $exercise) {
                    $pivot[$exercise->id] = [
                        'position' => $position,
                        'completed' => fake()->boolean(40),
                    ];
                }

                $session->exercises()->attach($pivot);
            });
    }
}
