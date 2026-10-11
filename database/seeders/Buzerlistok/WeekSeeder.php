<?php

namespace Database\Seeders\Buzerlistok;

use App\Enums\Buzerlistok\MarkStatus;
use App\Models\Buzerlistok\Week;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class WeekSeeder extends Seeder
{
    /**
     * Seeds the last four weeks with goals and marks for every day up to today.
     */
    public function run(): void
    {
        $goalNames = ['Exercise', 'Read 30 minutes', 'No sugar', 'Meditate', 'Sleep before 23:00'];

        $comments = ['Rest day', 'Travelling', 'Sick', 'Family visit'];

        $today = Carbon::today();

        foreach (range(3, 0) as $weeksAgo) {
            $startsOn = $today->copy()->startOfWeek()->subWeeks($weeksAgo);

            $week = Week::firstOrCreate(['starts_on' => $startsOn->toDateString()]);

            if ($week->goals()->exists()) {
                continue;
            }

            foreach ($goalNames as $position => $name) {
                $goal = $week->goals()->create([
                    'name' => $name,
                    'position' => $position,
                ]);

                foreach (range(0, 6) as $offset) {
                    $day = $startsOn->copy()->addDays($offset);

                    if ($day->isAfter($today)) {
                        break;
                    }

                    $status = fake()->randomElement([
                        MarkStatus::Success,
                        MarkStatus::Success,
                        MarkStatus::Success,
                        MarkStatus::Partial,
                        MarkStatus::Fail,
                        MarkStatus::NotApplicable,
                        null,
                    ]);

                    if ($status === null) {
                        continue;
                    }

                    $goal->marks()->create([
                        'marked_on' => $day->toDateString(),
                        'status' => $status,
                        'comment' => $status === MarkStatus::NotApplicable ? fake()->randomElement($comments) : null,
                    ]);
                }
            }
        }
    }
}
