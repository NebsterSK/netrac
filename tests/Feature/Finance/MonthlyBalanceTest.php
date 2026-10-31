<?php

use App\Models\Finance\Statement;
use App\Models\Finance\Vault;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('finance.monthly-balance.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('finance.monthly-balance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('finance/MonthlyBalance'));
    });
});

it('derives each balance from the account change since the previous statement, newest first', function () {
    $account = Vault::monthlyBalanceVault();

    Statement::factory()->withAmount($account, 1000)->create(['date' => '2024-01-01']);
    Statement::factory()->withAmount($account, 1500)->create(['date' => '2024-02-01', 'note' => 'Bonus']);
    Statement::factory()->withAmount($account, 1200)->create(['date' => '2024-03-01']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('balances', 2)
            ->where('balances.0.date', '2024-03-01')
            ->where('balances.0.amount', -300)
            ->where('balances.0.note', null)
            ->where('balances.1.date', '2024-02-01')
            ->where('balances.1.amount', 500)
            ->where('balances.1.note', 'Bonus'));
});

it('follows whichever vault tracks the monthly balance', function () {
    Vault::query()->update(['tracks_monthly_balance' => false]);
    $savings = Vault::factory()->create(['tracks_monthly_balance' => true]);

    Statement::factory()->withAmount($savings, 300)->create(['date' => '2024-01-01']);
    Statement::factory()->withAmount($savings, 350)->create(['date' => '2024-02-01']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('balances', 1)
            ->where('balances.0.amount', 50));
});

it('yields no balances when no vault tracks the monthly balance', function () {
    Vault::query()->update(['tracks_monthly_balance' => false]);

    Statement::factory()->create(['date' => '2024-01-01']);
    Statement::factory()->create(['date' => '2024-02-01']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page->has('balances', 0));
});

it('yields no balance from a single statement', function () {
    Statement::factory()->create(['date' => '2024-01-01']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page->has('balances', 0));
});

it('marks the balance of a draft statement as a draft', function () {
    $account = Vault::monthlyBalanceVault();

    Statement::factory()->withAmount($account, 1000)->create(['date' => '2024-01-01']);
    Statement::factory()->draft()->withAmount($account, 1400)->create(['date' => '2024-02-01']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('balances.0.amount', 400)
            ->where('balances.0.is_draft', true));
});

it('no longer accepts manual balances', function () {
    $this->actingAs(verifiedUser())
        ->post('/finance/monthly-balance', ['date' => '2024-05-01', 'amount' => 1500])
        ->assertMethodNotAllowed();
});
