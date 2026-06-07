<?php

use App\Models\Finance\MonthlyBalance;
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

it('lists balances newest first', function () {
    MonthlyBalance::factory()->create(['date' => '2024-01-01', 'amount' => 100]);
    MonthlyBalance::factory()->create(['date' => '2024-03-01', 'amount' => 300]);

    $this->actingAs(verifiedUser())
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/MonthlyBalance')
            ->has('balances', 2)
            ->where('balances.0.amount', 300)
            ->has('existingDates', 2));
});

it('stores a balance', function () {
    $this->actingAs(verifiedUser())
        ->post(route('finance.monthly-balance.store'), [
            'date' => '2024-05-01',
            'amount' => 1500,
            'comment' => 'Savings',
        ])
        ->assertRedirect(route('finance.monthly-balance.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('monthly_balances', ['amount' => 1500, 'comment' => 'Savings']);
    expect(MonthlyBalance::sole()->date->toDateString())->toBe('2024-05-01');
});

it('requires date and amount on store', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.monthly-balance.index'))
        ->post(route('finance.monthly-balance.store'), [])
        ->assertSessionHasErrors(['date', 'amount']);
});

it('rejects a duplicate date on store', function () {
    MonthlyBalance::factory()->create(['date' => '2024-06-01']);

    $this->actingAs(verifiedUser())
        ->from(route('finance.monthly-balance.index'))
        ->post(route('finance.monthly-balance.store'), [
            'date' => '2024-06-01',
            'amount' => 200,
        ])
        ->assertSessionHasErrors('date');
});

it('blocks guests from storing', function () {
    $this->post(route('finance.monthly-balance.store'), [
        'date' => '2024-05-01',
        'amount' => 1500,
    ])->assertRedirect(route('login'));

    expect(MonthlyBalance::count())->toBe(0);
});

it('updates a balance', function () {
    $balance = MonthlyBalance::factory()->create(['date' => '2024-07-01', 'amount' => 100]);

    $this->actingAs(verifiedUser())
        ->patch(route('finance.monthly-balance.update', $balance), [
            'date' => '2024-08-01',
            'amount' => 250,
            'comment' => null,
        ])
        ->assertRedirect(route('finance.monthly-balance.index'))
        ->assertSessionHas('success');

    expect($balance->refresh()->amount)->toBe(250)
        ->and($balance->date->toDateString())->toBe('2024-08-01');
});

it('allows keeping the same date on update', function () {
    $balance = MonthlyBalance::factory()->create(['date' => '2024-09-01']);

    $this->actingAs(verifiedUser())
        ->patch(route('finance.monthly-balance.update', $balance), [
            'date' => '2024-09-01',
            'amount' => 999,
            'comment' => null,
        ])
        ->assertSessionHasNoErrors();
});

it('deletes a balance', function () {
    $balance = MonthlyBalance::factory()->create();

    $this->actingAs(verifiedUser())
        ->delete(route('finance.monthly-balance.destroy', $balance))
        ->assertRedirect(route('finance.monthly-balance.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($balance);
});
