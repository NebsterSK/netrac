<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->cash = Asset::factory()->tracksMonthlyBalance()->create();
});

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

it('derives each balance from the tracked asset change since the previous statement, newest first', function () {
    Statement::factory()->withBalance($this->cash, 1000)->create(['date' => '2024-01-01']);
    Statement::factory()->withBalance($this->cash, 1500)->create(['date' => '2024-02-01', 'note' => 'Bonus']);
    Statement::factory()->withBalance($this->cash, 1200)->create(['date' => '2024-03-01']);

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

it('ignores assets that do not track the monthly balance', function () {
    $broker = Asset::factory()->create();

    Statement::factory()->withBalance($this->cash, 1000)->withBalance($broker, 5000)->create(['date' => '2024-01-01']);
    Statement::factory()->withBalance($this->cash, 1100)->withBalance($broker, 9000)->create(['date' => '2024-02-01']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page->where('balances.0.amount', 100));
});

it('yields no balances without a tracked asset', function () {
    $this->cash->update(['tracks_monthly_balance' => false]);

    Statement::factory()->withBalance($this->cash, 1000)->create(['date' => '2024-01-01']);
    Statement::factory()->withBalance($this->cash, 1100)->create(['date' => '2024-02-01']);

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
    Statement::factory()->withBalance($this->cash, 1000)->create(['date' => '2024-01-01']);
    Statement::factory()->draft()->withBalance($this->cash, 1400)->create(['date' => '2024-02-01']);

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
