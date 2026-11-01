<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

it('turns the former hardcoded balance columns into assets owned by the first user', function () {
    Artisan::call('migrate:rollback', ['--step' => 4]);

    $ownerId = DB::table('users')->insertGetId(['name' => 'Lukas', 'email' => 'first@example.com', 'password' => 'secret']);
    DB::table('users')->insert(['name' => 'Lucka', 'email' => 'second@example.com', 'password' => 'secret']);
    DB::table('statements')->insert(['date' => '2026-01-01', 'account' => 1200, 'legacy_upgrade' => 0, 'uniqa_sds' => 0, 'uniqa_dds' => 0, 'finax' => 300, 'trading212' => 0]);

    try {
        Artisan::call('migrate');

        expect(Asset::orderBy('id')->get(['user_id', 'name', 'color', 'tracks_monthly_balance'])->toArray())->toBe([
            ['user_id' => $ownerId, 'name' => 'Account', 'color' => '#9ca3af', 'tracks_monthly_balance' => true],
            ['user_id' => $ownerId, 'name' => 'Legacy Upgrade', 'color' => '#0559d2', 'tracks_monthly_balance' => false],
            ['user_id' => $ownerId, 'name' => 'Uniqa SDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
            ['user_id' => $ownerId, 'name' => 'Uniqa DDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
            ['user_id' => $ownerId, 'name' => 'Finax', 'color' => '#cbe7ff', 'tracks_monthly_balance' => false],
            ['user_id' => $ownerId, 'name' => 'Trading 212', 'color' => '#00abe0', 'tracks_monthly_balance' => false],
        ])
            ->and(Statement::sole()->user_id)->toBe($ownerId)
            ->and(Statement::sole()->assets()->where('name', 'Finax')->sole()->pivot->amount)->toBe(300);
    } finally {
        Artisan::call('migrate');
        DB::table('users')->whereIn('email', ['first@example.com', 'second@example.com'])->delete();
    }
});

it('lists assets by owner then name with statement counts', function () {
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);

    $broker = Asset::factory()->for($alice, 'owner')->create(['name' => 'Broker']);
    Asset::factory()->for($bob, 'owner')->create(['name' => 'Account']);
    Asset::factory()->for($alice, 'owner')->create(['name' => 'Savings']);

    Statement::factory()->count(2)->withAmount($broker, 100)->create(['user_id' => $alice->id]);

    $this->actingAs(verifiedUser())
        ->get(route('finance.assets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('assets', 3)
            ->where('assets.0.name', 'Broker')
            ->where('assets.0.user_id', $alice->id)
            ->where('assets.0.statements_count', 2)
            ->where('assets.1.name', 'Savings')
            ->where('assets.2.name', 'Account'));
});

it('respects the owner filter on the index', function () {
    $other = User::factory()->create();
    $viewer = verifiedUser(['finance_owner_id' => $other->id]);

    Asset::factory()->for($other, 'owner')->create(['name' => 'Theirs']);
    Asset::factory()->for($viewer, 'owner')->create(['name' => 'Mine']);

    $this->actingAs($viewer)
        ->get(route('finance.assets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('assets', 1)
            ->where('assets.0.name', 'Theirs'));
});

it('stores an asset', function () {
    $owner = verifiedUser();

    $this->actingAs($owner)
        ->post(route('finance.assets.store'), ['user_id' => $owner->id, 'name' => 'Savings', 'color' => '#22c55e', 'tracks_monthly_balance' => false])
        ->assertRedirect(route('finance.assets.index'))
        ->assertSessionHas('success');

    $asset = Asset::where('name', 'Savings')->sole();

    expect($asset->color)->toBe('#22c55e')
        ->and($asset->tracks_monthly_balance)->toBeFalse()
        ->and($asset->user_id)->toBe($owner->id);
});

it('validates asset input', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.assets.index'))
        ->post(route('finance.assets.store'), ['name' => '', 'color' => 'blue'])
        ->assertSessionHasErrors(['user_id', 'name', 'color', 'tracks_monthly_balance']);
});

it('rejects an unknown owner', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.assets.index'))
        ->post(route('finance.assets.store'), ['user_id' => 999999, 'name' => 'Savings', 'tracks_monthly_balance' => false])
        ->assertSessionHasErrors('user_id');
});

it('rejects a duplicate asset name for the same owner', function () {
    $owner = verifiedUser();
    Asset::factory()->for($owner, 'owner')->create(['name' => 'Finax']);

    $this->actingAs($owner)
        ->from(route('finance.assets.index'))
        ->post(route('finance.assets.store'), ['user_id' => $owner->id, 'name' => 'Finax', 'tracks_monthly_balance' => false])
        ->assertSessionHasErrors('name');
});

it('allows the same asset name for different owners', function () {
    $owner = verifiedUser();
    $other = User::factory()->create();
    Asset::factory()->for($owner, 'owner')->create(['name' => 'Finax']);

    $this->actingAs($owner)
        ->post(route('finance.assets.store'), ['user_id' => $other->id, 'name' => 'Finax', 'tracks_monthly_balance' => false])
        ->assertSessionHasNoErrors();

    expect(Asset::where('name', 'Finax')->count())->toBe(2);
});

it('blocks guests from storing', function () {
    $this->post(route('finance.assets.store'), ['name' => 'Savings', 'tracks_monthly_balance' => false])
        ->assertRedirect(route('login'));

    $this->assertDatabaseMissing('assets', ['name' => 'Savings']);
});

it('updates an asset', function () {
    $asset = Asset::factory()->create(['name' => 'Old']);

    $this->actingAs(verifiedUser())
        ->put(route('finance.assets.update', $asset), ['user_id' => $asset->user_id, 'name' => 'New', 'color' => null, 'tracks_monthly_balance' => false])
        ->assertRedirect(route('finance.assets.index'))
        ->assertSessionHas('success');

    expect($asset->refresh()->name)->toBe('New')
        ->and($asset->color)->toBeNull();
});

it('keeps its own name when updating', function () {
    $asset = Asset::factory()->create(['name' => 'Same']);

    $this->actingAs(verifiedUser())
        ->put(route('finance.assets.update', $asset), ['user_id' => $asset->user_id, 'name' => 'Same', 'tracks_monthly_balance' => false])
        ->assertSessionHasNoErrors();
});

it('blocks changing the owner when the asset has recorded amounts', function () {
    $asset = Asset::factory()->create();
    Statement::factory()->withAmount($asset, 500)->create(['user_id' => $asset->user_id]);
    $other = User::factory()->create();

    $this->actingAs(verifiedUser())
        ->from(route('finance.assets.index'))
        ->put(route('finance.assets.update', $asset), ['user_id' => $other->id, 'name' => $asset->name, 'tracks_monthly_balance' => false])
        ->assertSessionHasErrors(['user_id' => 'Asset already has recorded amounts; create a new asset for the other owner.']);
});

it('allows changing the owner when the asset has no recorded amounts', function () {
    $asset = Asset::factory()->create();
    $other = User::factory()->create();

    $this->actingAs(verifiedUser())
        ->put(route('finance.assets.update', $asset), ['user_id' => $other->id, 'name' => $asset->name, 'tracks_monthly_balance' => false])
        ->assertSessionHasNoErrors();

    expect($asset->refresh()->user_id)->toBe($other->id);
});

it('lets only one asset per owner track the monthly balance', function () {
    $owner = verifiedUser();
    $other = User::factory()->create();
    $previous = Asset::factory()->for($owner, 'owner')->tracksMonthlyBalance()->create();
    $otherTracked = Asset::factory()->for($other, 'owner')->tracksMonthlyBalance()->create();
    $asset = Asset::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->put(route('finance.assets.update', $asset), ['user_id' => $owner->id, 'name' => $asset->name, 'tracks_monthly_balance' => true])
        ->assertSessionHasNoErrors();

    expect($asset->refresh()->tracks_monthly_balance)->toBeTrue()
        ->and($previous->refresh()->tracks_monthly_balance)->toBeFalse()
        ->and($otherTracked->refresh()->tracks_monthly_balance)->toBeTrue();
});

it('lets only the newly stored asset of its owner track the monthly balance', function () {
    $owner = verifiedUser();
    $previous = Asset::factory()->for($owner, 'owner')->tracksMonthlyBalance()->create();

    $this->actingAs($owner)
        ->post(route('finance.assets.store'), ['user_id' => $owner->id, 'name' => 'Tracked', 'tracks_monthly_balance' => true])
        ->assertSessionHasNoErrors();

    $tracked = Asset::where('name', 'Tracked')->sole();

    expect(Asset::where('tracks_monthly_balance', true)->pluck('id')->all())->toBe([$tracked->id])
        ->and($previous->refresh()->tracks_monthly_balance)->toBeFalse();
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
