<?php

use App\Models\Finance\Statement;
use Inertia\Testing\AssertableInertia as Assert;

function statementPayload(array $overrides = []): array
{
    return array_merge([
        'date' => '2024-05-01',
        'account' => 1000,
        'legacy_upgrade' => 2000,
        'uniqa_sds' => 3000,
        'uniqa_dds' => 4000,
        'finax' => 5000,
        'trading212' => 6000,
    ], $overrides);
}

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('finance.net-worth.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('finance.net-worth.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('finance/NetWorth'));
    });
});

it('lists statements newest first', function () {
    Statement::factory()->create(['date' => '2024-01-01']);
    Statement::factory()->create(['date' => '2024-03-01', 'account' => 12345]);

    $this->actingAs(verifiedUser())
        ->get(route('finance.net-worth.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/NetWorth')
            ->has('statements', 2)
            ->where('statements.0.account', 12345));
});

it('stores a statement', function () {
    $this->actingAs(verifiedUser())
        ->post(route('finance.net-worth.store'), statementPayload())
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('statements', ['account' => 1000, 'trading212' => 6000]);
});

it('requires every balance column on store', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), [])
        ->assertSessionHasErrors(['date', 'account', 'legacy_upgrade', 'uniqa_sds', 'uniqa_dds', 'finax', 'trading212']);
});

it('rejects negative balances', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), statementPayload(['account' => -1]))
        ->assertSessionHasErrors('account');
});

it('rejects a duplicate date on store', function () {
    Statement::factory()->create(['date' => '2024-06-01']);

    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), statementPayload(['date' => '2024-06-01']))
        ->assertSessionHasErrors('date');
});

it('blocks guests from storing', function () {
    $this->post(route('finance.net-worth.store'), statementPayload())
        ->assertRedirect(route('login'));

    expect(Statement::count())->toBe(0);
});

it('updates a statement', function () {
    $statement = Statement::factory()->create(['date' => '2024-07-01', 'account' => 100]);

    $this->actingAs(verifiedUser())
        ->patch(route('finance.net-worth.update', $statement), statementPayload(['date' => '2024-08-01', 'account' => 777]))
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    expect($statement->refresh()->account)->toBe(777)
        ->and($statement->date->toDateString())->toBe('2024-08-01');
});

it('deletes a statement', function () {
    $statement = Statement::factory()->create();

    $this->actingAs(verifiedUser())
        ->delete(route('finance.net-worth.destroy', $statement))
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($statement);
});
