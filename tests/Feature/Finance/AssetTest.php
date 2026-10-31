<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Inertia\Testing\AssertableInertia as Assert;

function assetPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Broker',
        'color' => '#3b82f6',
        'tracks_monthly_balance' => false,
    ], $overrides);
}

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

it('lists assets in position order with statement counts', function () {
    $pension = Asset::factory()->create(['name' => 'Pension', 'position' => 2]);
    Asset::factory()->create(['name' => 'Account', 'position' => 1]);

    Statement::factory()->withBalance($pension, 100)->create();
    Statement::factory()->withBalance($pension, 200)->create();

    $this->actingAs(verifiedUser())
        ->get(route('finance.assets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('assets', 2)
            ->where('assets.0.name', 'Account')
            ->where('assets.1.name', 'Pension')
            ->where('assets.1.balances_count', 2));
});

it('stores an asset after the last position', function () {
    Asset::factory()->create(['position' => 4]);

    $this->actingAs(verifiedUser())
        ->post(route('finance.assets.store'), assetPayload())
        ->assertRedirect(route('finance.assets.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('assets', ['name' => 'Broker', 'color' => '#3b82f6', 'position' => 5]);
});

it('rejects a duplicate asset name', function () {
    Asset::factory()->create(['name' => 'Broker']);

    $this->actingAs(verifiedUser())
        ->from(route('finance.assets.index'))
        ->post(route('finance.assets.store'), assetPayload())
        ->assertSessionHasErrors('name');
});

it('validates asset input', function (array $overrides, string $field) {
    $this->actingAs(verifiedUser())
        ->from(route('finance.assets.index'))
        ->post(route('finance.assets.store'), assetPayload($overrides))
        ->assertSessionHasErrors($field);

    expect(Asset::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid color' => [['color' => 'red'], 'color'],
    'missing tracking flag' => [['tracks_monthly_balance' => null], 'tracks_monthly_balance'],
]);

it('blocks guests from storing', function () {
    $this->post(route('finance.assets.store'), assetPayload())
        ->assertRedirect(route('login'));

    expect(Asset::count())->toBe(0);
});

it('updates an asset', function () {
    $asset = Asset::factory()->create(['name' => 'Old']);

    $this->actingAs(verifiedUser())
        ->put(route('finance.assets.update', $asset), assetPayload(['name' => 'New', 'color' => null]))
        ->assertRedirect(route('finance.assets.index'))
        ->assertSessionHas('success');

    expect($asset->refresh()->name)->toBe('New')
        ->and($asset->color)->toBeNull();
});

it('keeps the same name on update', function () {
    $asset = Asset::factory()->create(['name' => 'Broker']);

    $this->actingAs(verifiedUser())
        ->put(route('finance.assets.update', $asset), assetPayload())
        ->assertSessionHasNoErrors();
});

it('lets only one asset track the monthly balance', function () {
    $current = Asset::factory()->tracksMonthlyBalance()->create();
    $asset = Asset::factory()->create();

    $this->actingAs(verifiedUser())
        ->put(route('finance.assets.update', $asset), assetPayload(['tracks_monthly_balance' => true]))
        ->assertSessionHasNoErrors();

    expect($asset->refresh()->tracks_monthly_balance)->toBeTrue()
        ->and($current->refresh()->tracks_monthly_balance)->toBeFalse();
});

it('deletes an asset and its balances, keeping the statements', function () {
    $asset = Asset::factory()->create();
    $statement = Statement::factory()->withBalance($asset, 500)->create();

    $this->actingAs(verifiedUser())
        ->delete(route('finance.assets.destroy', $asset))
        ->assertRedirect(route('finance.assets.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($asset);
    $this->assertModelExists($statement);
    $this->assertDatabaseCount('statement_balances', 0);
});

describe('reorder', function () {
    it('blocks guests from reordering', function () {
        $asset = Asset::factory()->create();

        $this->put(route('finance.assets.reorder'), ['ids' => [$asset->id]])
            ->assertRedirect(route('login'));
    });

    it('saves the given order as positions', function () {
        $first = Asset::factory()->create(['position' => 1]);
        $second = Asset::factory()->create(['position' => 2]);

        $this->actingAs(verifiedUser())
            ->from(route('finance.assets.index'))
            ->put(route('finance.assets.reorder'), ['ids' => [$second->id, $first->id]])
            ->assertRedirect(route('finance.assets.index'))
            ->assertSessionHasNoErrors();

        expect($second->refresh()->position)->toBe(1)
            ->and($first->refresh()->position)->toBe(2);
    });

    it('rejects unknown or duplicate ids', function () {
        $asset = Asset::factory()->create();

        $this->actingAs(verifiedUser())
            ->from(route('finance.assets.index'))
            ->put(route('finance.assets.reorder'), ['ids' => [$asset->id, $asset->id, 999999]])
            ->assertSessionHasErrors(['ids.0', 'ids.2']);
    });
});
