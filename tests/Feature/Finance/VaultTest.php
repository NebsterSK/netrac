<?php

use App\Models\Finance\Statement;
use App\Models\Finance\Vault;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('finance.vaults.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('finance.vaults.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('finance/Vault')
                ->has('vaults'));
    });
});

it('turns the former hardcoded balance columns into vaults', function () {
    expect(Vault::orderBy('position')->get(['name', 'color', 'tracks_monthly_balance'])->toArray())->toBe([
        ['name' => 'Account', 'color' => '#9ca3af', 'tracks_monthly_balance' => true],
        ['name' => 'Legacy Upgrade', 'color' => '#0559d2', 'tracks_monthly_balance' => false],
        ['name' => 'Uniqa SDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
        ['name' => 'Uniqa DDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
        ['name' => 'Finax', 'color' => '#cbe7ff', 'tracks_monthly_balance' => false],
        ['name' => 'Trading 212', 'color' => '#00abe0', 'tracks_monthly_balance' => false],
    ]);
});

it('lists vaults in their saved order with statement counts', function () {
    $last = Vault::factory()->create(['name' => 'Zeta', 'position' => 1000]);

    Statement::factory()->count(2)->withAmount($last, 100)->create();

    $this->actingAs(verifiedUser())
        ->get(route('finance.vaults.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('vaults', 7)
            ->where('vaults.0.name', 'Account')
            ->where('vaults.6.name', 'Zeta')
            ->where('vaults.6.statements_count', 2));
});

it('stores a vault at the end of the order', function () {
    $this->actingAs(verifiedUser())
        ->post(route('finance.vaults.store'), ['name' => 'Savings', 'color' => '#22c55e', 'tracks_monthly_balance' => false])
        ->assertRedirect(route('finance.vaults.index'))
        ->assertSessionHas('success');

    $vault = Vault::where('name', 'Savings')->sole();

    expect($vault->color)->toBe('#22c55e')
        ->and($vault->position)->toBe((int) Vault::max('position'));
});

it('validates vault input', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.vaults.index'))
        ->post(route('finance.vaults.store'), ['name' => '', 'color' => 'blue'])
        ->assertSessionHasErrors(['name', 'color', 'tracks_monthly_balance']);
});

it('rejects a duplicate vault name', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.vaults.index'))
        ->post(route('finance.vaults.store'), ['name' => 'Finax', 'tracks_monthly_balance' => false])
        ->assertSessionHasErrors('name');
});

it('blocks guests from storing', function () {
    $this->post(route('finance.vaults.store'), ['name' => 'Savings', 'tracks_monthly_balance' => false])
        ->assertRedirect(route('login'));

    $this->assertDatabaseMissing('vaults', ['name' => 'Savings']);
});

it('updates a vault', function () {
    $vault = Vault::factory()->create(['name' => 'Old']);

    $this->actingAs(verifiedUser())
        ->put(route('finance.vaults.update', $vault), ['name' => 'New', 'color' => null, 'tracks_monthly_balance' => false])
        ->assertRedirect(route('finance.vaults.index'))
        ->assertSessionHas('success');

    expect($vault->refresh()->name)->toBe('New')
        ->and($vault->color)->toBeNull();
});

it('keeps its own name when updating', function () {
    $vault = Vault::factory()->create(['name' => 'Same']);

    $this->actingAs(verifiedUser())
        ->put(route('finance.vaults.update', $vault), ['name' => 'Same', 'tracks_monthly_balance' => false])
        ->assertSessionHasNoErrors();
});

it('lets only one vault track the monthly balance', function () {
    $vault = Vault::factory()->create();

    $this->actingAs(verifiedUser())
        ->put(route('finance.vaults.update', $vault), ['name' => $vault->name, 'tracks_monthly_balance' => true])
        ->assertSessionHasNoErrors();

    expect(Vault::where('tracks_monthly_balance', true)->pluck('id')->all())->toBe([$vault->id]);
});

it('reorders vaults', function () {
    $ids = Vault::orderBy('position')->pluck('id')->reverse()->values()->all();

    $this->actingAs(verifiedUser())
        ->from(route('finance.vaults.index'))
        ->put(route('finance.vaults.reorder'), ['ids' => $ids])
        ->assertRedirect(route('finance.vaults.index'));

    expect(Vault::orderBy('position')->pluck('id')->all())->toBe($ids);
});

it('deletes a vault with its statement amounts', function () {
    $vault = Vault::factory()->create();
    $statement = Statement::factory()->withAmount($vault, 500)->create();

    $this->actingAs(verifiedUser())
        ->delete(route('finance.vaults.destroy', $vault))
        ->assertRedirect(route('finance.vaults.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($vault);
    $this->assertModelExists($statement);
    $this->assertDatabaseMissing('statement_vault', ['vault_id' => $vault->id]);
});
