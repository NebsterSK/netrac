<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->put(route('finance.owner-filter.update'), ['owner_id' => null])
            ->assertRedirect(route('login'));
    });
});

it('sets and clears the owner filter', function () {
    $user = verifiedUser();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->from(route('finance.assets.index'))
        ->put(route('finance.owner-filter.update'), ['owner_id' => $other->id])
        ->assertRedirect(route('finance.assets.index'));

    expect($user->fresh()->financeOwnerFilter())->toBe($other->id);

    $this->actingAs($user)
        ->put(route('finance.owner-filter.update'), ['owner_id' => null])
        ->assertRedirect();

    expect($user->fresh()->financeOwnerFilter())->toBeNull();
});

it('drops the page but keeps other query params when switching the filter', function () {
    $user = verifiedUser();

    $this->actingAs($user)
        ->from(route('finance.expenses.index', ['filter' => ['name' => 'rent'], 'sort' => '-amount', 'page' => 3]))
        ->put(route('finance.owner-filter.update'), ['owner_id' => $user->id])
        ->assertRedirect(route('finance.expenses.index', ['filter' => ['name' => 'rent'], 'sort' => '-amount']));
});

it('rejects an invalid or unknown owner', function (mixed $ownerId) {
    $user = verifiedUser();

    $this->actingAs($user)
        ->put(route('finance.owner-filter.update'), ['owner_id' => $ownerId])
        ->assertSessionHasErrors('owner_id');

    expect($user->fresh()->finance_owner_id)->toBeNull();
})->with([999999, 'abc']);

it('shares owners and the current filter on inertia pages', function () {
    $user = verifiedUser();
    $other = User::factory()->create(['finance_owner_id' => null]);
    $user->update(['finance_owner_id' => $other->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('ownerFilter', $other->id)
            ->has('owners', User::count())
            ->where('owners.0.id', User::orderBy('id')->value('id'))
            ->has('owners.0', fn (Assert $owner) => $owner->hasAll(['id', 'name', 'color'])));
});
