<?php

namespace Database\Seeders;

use App\Models\Statement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StatementSeeder extends Seeder
{
    public function run(): void
    {
        $account = 5000;
        $legacyUpgrade = 3000;
        $uniquaSds = 2000;
        $uniquaDds = 1500;
        $finax = 4000;
        $trading212 = 2500;

        $start = Carbon::now()->subMonths(24)->startOfMonth();

        for ($idx = 0; $idx < 24; $idx++) {
            $date = $start->copy()->addMonths($idx);

            Statement::create([
                'date' => $date->toDateString(),
                'account' => $account += rand(80, 150) * (rand(1, 5) === 1 ? -1 : 1),
                'legacy_upgrade' => $legacyUpgrade += rand(50, 120) * (rand(1, 5) === 1 ? -1 : 1),
                'uniqua_sds' => $uniquaSds += rand(40, 100) * (rand(1, 5) === 1 ? -1 : 1),
                'uniqua_dds' => $uniquaDds += rand(30, 90) * (rand(1, 5) === 1 ? -1 : 1),
                'finax' => $finax += rand(60, 130) * (rand(1, 5) === 1 ? -1 : 1),
                'trading212' => $trading212 += rand(70, 140) * (rand(1, 5) === 1 ? -1 : 1),
            ]);
        }
    }
}
