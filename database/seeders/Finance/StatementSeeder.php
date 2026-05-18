<?php

namespace Database\Seeders\Finance;

use App\Models\Finance\Statement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StatementSeeder extends Seeder
{
    public function run(): void
    {
        $account = 5000;
        $legacyUpgrade = 3000;
        $uniqaSds = 2000;
        $uniqaDds = 1500;
        $finax = 4000;
        $trading212 = 2500;

        $start = Carbon::now()->subMonths(24)->startOfMonth();

        for ($idx = 0; $idx < 24; $idx++) {
            $date = $start->copy()->addMonths($idx);

            Statement::create([
                'date' => $date->toDateString(),
                'account' => $account += rand(80, 150) * (rand(1, 5) === 1 ? -1 : 1),
                'legacy_upgrade' => $legacyUpgrade += rand(50, 120) * (rand(1, 5) === 1 ? -1 : 1),
                'uniqa_sds' => $uniqaSds += rand(40, 100) * (rand(1, 5) === 1 ? -1 : 1),
                'uniqa_dds' => $uniqaDds += rand(30, 90) * (rand(1, 5) === 1 ? -1 : 1),
                'finax' => $finax += rand(60, 130) * (rand(1, 5) === 1 ? -1 : 1),
                'trading212' => $trading212 += rand(70, 140) * (rand(1, 5) === 1 ? -1 : 1),
            ]);
        }
    }
}
