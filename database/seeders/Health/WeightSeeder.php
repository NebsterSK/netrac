<?php

namespace Database\Seeders\Health;

use App\Models\Health\Weight;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WeightSeeder extends Seeder
{
    /**
     * Seed about half a year of weigh-ins at irregular intervals, drifting
     * slowly downwards, so the chart's date spacing is visible.
     */
    public function run(): void
    {
        $date = Carbon::today()->subMonths(6);
        $weight = 86.0;

        while ($date->lte(Carbon::today())) {
            Weight::create(['date' => $date->toDateString(), 'weight' => round($weight, 1)]);

            $date->addDays(rand(2, 14));
            $weight += rand(-12, 6) / 10;
        }
    }
}
