<?php

use App\Models\Finance\Statement;
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
    Statement::factory()->create(['date' => '2024-01-01', 'account' => 1000]);
    Statement::factory()->create(['date' => '2024-02-01', 'account' => 1500, 'note' => 'Bonus']);
    Statement::factory()->create(['date' => '2024-03-01', 'account' => 1200]);

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

it('yields no balance from a single statement', function () {
    Statement::factory()->create(['date' => '2024-01-01']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page->has('balances', 0));
});

it('marks the balance of a draft statement as a draft', function () {
    Statement::factory()->create(['date' => '2024-01-01', 'account' => 1000]);
    Statement::factory()->draft()->create(['date' => '2024-02-01', 'account' => 1400]);

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
