<?php

namespace Database\Seeders\Finance;

use App\Models\Finance\MonthlyBalance;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MonthlyBalanceSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $start = Carbon::now()->subMonths(23)->startOfMonth();

        for ($month = 0; $month < 24; $month++) {
            MonthlyBalance::factory()->create([
                'date' => $start->copy()->addMonths($month),
            ]);
        }
    }
}
