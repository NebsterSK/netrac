<?php

namespace Database\Seeders\Finance;

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StatementSeeder extends Seeder
{
    /**
     * Assets (name => [colour, tracks monthly balance, retirement]), the number of months
     * to skip before the first statement and the month indexes (0–23) left
     * without a statement, per user email. Lucka starts a few months later,
     * skips one month and hasn't entered the latest month yet, so the
     * "waiting for" state shows up locally.
     *
     * @var array<string, array{skip_months: int, missing_months: array<int, int>, assets: array<string, array{string, bool, bool}>}>
     */
    private const array PROFILES = [
        'lukas@example.com' => [
            'skip_months' => 0,
            'missing_months' => [],
            'assets' => [
                'Account' => ['#9ca3af', true, false],
                'Legacy Upgrade' => ['#0559d2', false, false],
                'Uniqa SDS' => ['#005ca9', false, true],
                'Uniqa DDS' => ['#005ca9', false, true],
                'Finax' => ['#cbe7ff', false, false],
                'Trading 212' => ['#00abe0', false, false],
            ],
        ],
        'lucka@example.com' => [
            'skip_months' => 4,
            'missing_months' => [12, 23],
            'assets' => [
                'Account' => ['#f9a8d4', true, false],
                'Savings' => ['#be185d', false, false],
            ],
        ],
    ];

    /**
     * Seed each user's assets and up to two years of statements, each asset
     * drifting from a random starting balance.
     */
    public function run(): void
    {
        foreach (self::PROFILES as $email => $profile) {
            $user = User::where('email', $email)->firstOrFail();

            $assets = collect($profile['assets'])->map(
                fn (array $definition, string $name): Asset => Asset::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $name],
                    ['color' => $definition[0], 'tracks_monthly_balance' => $definition[1], 'is_retirement' => $definition[2]],
                ),
            )->values();

            $balances = $assets
                ->mapWithKeys(fn (Asset $asset): array => [$asset->id => rand(15, 50) * 100])
                ->all();

            $start = Carbon::now()->subMonths(24)->startOfMonth();

            for ($idx = $profile['skip_months']; $idx < 24; $idx++) {
                if (in_array($idx, $profile['missing_months'], true)) {
                    continue;
                }

                $statement = Statement::create([
                    'user_id' => $user->id,
                    'date' => $start->copy()->addMonths($idx)->toDateString(),
                ]);

                foreach ($assets as $asset) {
                    $balances[$asset->id] += rand(40, 150) * (rand(1, 5) === 1 ? -1 : 1);

                    $statement->assets()->attach($asset->id, ['amount' => max(0, $balances[$asset->id])]);
                }
            }
        }
    }
}
