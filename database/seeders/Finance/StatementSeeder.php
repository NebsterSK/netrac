<?php

namespace Database\Seeders\Finance;

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StatementSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<int, array{name: string, color: string, amount: int, step: array{int, int}}> $definitions */
        $definitions = [
            ['name' => 'Account', 'color' => '#9ca3af', 'amount' => 5000, 'step' => [80, 150]],
            ['name' => 'Legacy Upgrade', 'color' => '#0559d2', 'amount' => 3000, 'step' => [50, 120]],
            ['name' => 'Uniqa SDS', 'color' => '#005ca9', 'amount' => 2000, 'step' => [40, 100]],
            ['name' => 'Uniqa DDS', 'color' => '#005ca9', 'amount' => 1500, 'step' => [30, 90]],
            ['name' => 'Finax', 'color' => '#cbe7ff', 'amount' => 4000, 'step' => [60, 130]],
            ['name' => 'Trading 212', 'color' => '#00abe0', 'amount' => 2500, 'step' => [70, 140]],
        ];

        $assets = collect($definitions)->map(fn (array $definition, int $index): Asset => Asset::create([
            'name' => $definition['name'],
            'color' => $definition['color'],
            'position' => $index + 1,
            'tracks_monthly_balance' => $index === 0,
        ]));

        $amounts = array_column($definitions, 'amount');
        $start = Carbon::now()->subMonths(24)->startOfMonth();

        for ($month = 0; $month < 24; $month++) {
            $statement = Statement::create([
                'date' => $start->copy()->addMonths($month)->toDateString(),
            ]);

            foreach ($assets as $index => $asset) {
                [$min, $max] = $definitions[$index]['step'];
                $amounts[$index] += rand($min, $max) * (rand(1, 5) === 1 ? -1 : 1);

                $statement->balances()->create([
                    'asset_id' => $asset->id,
                    'amount' => $amounts[$index],
                ]);
            }
        }
    }
}
