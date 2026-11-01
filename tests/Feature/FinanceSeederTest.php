<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Expense;
use App\Models\Finance\Statement;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds separate finance data for Lukas and Lucka', function () {
    $this->seed(DatabaseSeeder::class);

    $lukas = User::where('email', 'lukas@example.com')->firstOrFail();
    $lucka = User::where('email', 'lucka@example.com')->firstOrFail();

    expect($lukas->color)->toBe('#3b82f6')
        ->and($lucka->color)->toBe('#ec4899')
        ->and($lukas->assets()->count())->toBe(6)
        ->and($lucka->assets()->count())->toBe(2)
        ->and($lucka->assets()->where('tracks_monthly_balance', true)->count())->toBe(1)
        ->and($lukas->expenses()->count())->toBeGreaterThan(0)
        ->and($lucka->expenses()->count())->toBeGreaterThan(0)
        ->and($lucka->statements()->count())->toBeLessThan($lukas->statements()->count())
        ->and(Asset::whereNull('user_id')->count() + Expense::whereNull('user_id')->count() + Statement::whereNull('user_id')->count())->toBe(0)
        ->and($lucka->statements()->min('date'))->toBeGreaterThan($lukas->statements()->min('date'))
        ->and($lucka->statements()->max('date'))->toBeLessThan($lukas->statements()->max('date'));
});
