<?php

namespace Database\Seeders\Finance;

use App\Models\Finance\Statement;
use App\Models\Finance\Vault;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StatementSeeder extends Seeder
{
    /**
     * Seed two years of statements for the vaults created by the migrations,
     * each vault drifting from a random starting balance.
     */
    public function run(): void
    {
        $vaults = Vault::orderBy('position')->get();

        $balances = $vaults
            ->mapWithKeys(fn (Vault $vault): array => [$vault->id => rand(15, 50) * 100])
            ->all();

        $start = Carbon::now()->subMonths(24)->startOfMonth();

        for ($idx = 0; $idx < 24; $idx++) {
            $statement = Statement::create([
                'date' => $start->copy()->addMonths($idx)->toDateString(),
            ]);

            foreach ($vaults as $vault) {
                $balances[$vault->id] += rand(40, 150) * (rand(1, 5) === 1 ? -1 : 1);

                $statement->vaults()->attach($vault->id, ['amount' => max(0, $balances[$vault->id])]);
            }
        }
    }
}
