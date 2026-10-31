<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('finance.assets.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('finance.assets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('finance/Asset')
                ->has('assets'));
    });
});

it('turns the former hardcoded balance columns into assets', function () {
    expect(Asset::orderBy('id')->get(['name', 'color', 'tracks_monthly_balance'])->toArray())->toBe([
        ['name' => 'Account', 'color' => '#9ca3af', 'tracks_monthly_balance' => true],
        ['name' => 'Legacy Upgrade', 'color' => '#0559d2', 'tracks_monthly_balance' => false],
        ['name' => 'Uniqa SDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
        ['name' => 'Uniqa DDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
        ['name' => 'Finax', 'color' => '#cbe7ff', 'tracks_monthly_balance' => false],
        ['name' => 'Trading 212', 'color' => '#00abe0', 'tracks_monthly_balance' => false],
    ]);
});

it('lists assets alphabetically with statement counts', function () {
    $broker = Asset::factory()->create(['name' => 'Broker']);

    Statement::factory()->count(2)->withAmount($broker, 100)->create();

    $this->actingAs(verifiedUser())
        ->get(route('finance.assets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('assets', 7)
            ->where('assets.0.name', 'Account')
            ->where('assets.1.name', 'Broker')
            ->where('assets.1.statements_count', 2)
            ->where('assets.6.name', 'Uniqa SDS'));
});

it('stores an asset', function () {
    $this->actingAs(verifiedUser())
        ->post(route('finance.assets.store'), ['name' => 'Savings', 'color' => '#22c55e', 'tracks_monthly_balance' => false])
        ->assertRedirect(route('finance.assets.index'))
        ->assertSessionHas('success');

    $asset = Asset::where('name', 'Savings')->sole();

    expect($asset->color)->toBe('#22c55e')
        ->and($asset->tracks_monthly_balance)->toBeFalse();
});

it('validates asset input', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.assets.index'))
        ->post(route('finance.assets.store'), ['name' => '', 'color' => 'blue'])
        ->assertSessionHasErrors(['name', 'color', 'tracks_monthly_balance']);
});

it('rejects a duplicate asset name', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.assets.index'))
        ->post(route('finance.assets.store'), ['name' => 'Finax', 'tracks_monthly_balance' => false])
        ->assertSessionHasErrors('name');
});

it('blocks guests from storing', function () {
    $this->post(route('finance.assets.store'), ['name' => 'Savings', 'tracks_monthly_balance' => false])
        ->assertRedirect(route('login'));

    $this->assertDatabaseMissing('assets', ['name' => 'Savings']);
});

it('updates an asset', function () {
    $asset = Asset::factory()->create(['name' => 'Old']);

    $this->actingAs(verifiedUser())
        ->put(route('finance.assets.update', $asset), ['name' => 'New', 'color' => null, 'tracks_monthly_balance' => false])
        ->assertRedirect(route('finance.assets.index'))
        ->assertSessionHas('success');

    expect($asset->refresh()->name)->toBe('New')
        ->and($asset->color)->toBeNull();
});

it('keeps its own name when updating', function () {
    $asset = Asset::factory()->create(['name' => 'Same']);

    $this->actingAs(verifiedUser())
        ->put(route('finance.assets.update', $asset), ['name' => 'Same', 'tracks_monthly_balance' => false])
        ->assertSessionHasNoErrors();
});

it('lets only one asset track the monthly balance', function () {
    $asset = Asset::factory()->create();

    $this->actingAs(verifiedUser())
        ->put(route('finance.assets.update', $asset), ['name' => $asset->name, 'tracks_monthly_balance' => true])
        ->assertSessionHasNoErrors();

    expect(Asset::where('tracks_monthly_balance', true)->pluck('id')->all())->toBe([$asset->id]);
});

it('lets only the newly stored asset track the monthly balance', function () {
    $this->actingAs(verifiedUser())
        ->post(route('finance.assets.store'), ['name' => 'Tracked', 'tracks_monthly_balance' => true])
        ->assertSessionHasNoErrors();

    $tracked = Asset::where('name', 'Tracked')->sole();

    expect(Asset::where('tracks_monthly_balance', true)->pluck('id')->all())->toBe([$tracked->id]);
});

it('deletes an asset with its statement amounts', function () {
    $asset = Asset::factory()->create();
    $statement = Statement::factory()->withAmount($asset, 500)->create();

    $this->actingAs(verifiedUser())
        ->delete(route('finance.assets.destroy', $asset))
        ->assertRedirect(route('finance.assets.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($asset);
    $this->assertModelExists($statement);
    $this->assertDatabaseMissing('asset_statement', ['asset_id' => $asset->id]);
});
