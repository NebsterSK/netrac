<?php

namespace Database\Seeders\Finance;

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StatementSeeder extends Seeder
{
    /**
     * Seed two years of statements for the assets created by the migrations,
     * each asset drifting from a random starting balance.
     */
    public function run(): void
    {
        $assets = Asset::orderBy('name')->get();

        $balances = $assets
            ->mapWithKeys(fn (Asset $asset): array => [$asset->id => rand(15, 50) * 100])
            ->all();

        $start = Carbon::now()->subMonths(24)->startOfMonth();

        for ($idx = 0; $idx < 24; $idx++) {
            $statement = Statement::create([
                'date' => $start->copy()->addMonths($idx)->toDateString(),
            ]);

            foreach ($assets as $asset) {
                $balances[$asset->id] += rand(40, 150) * (rand(1, 5) === 1 ? -1 : 1);

                $statement->assets()->attach($asset->id, ['amount' => max(0, $balances[$asset->id])]);
            }
        }
    }
}
