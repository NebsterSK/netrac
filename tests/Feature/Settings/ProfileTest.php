<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Expense;
use App\Models\Finance\Statement;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests away from the profile page', function () {
    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});

it('shows the profile page to authenticated users', function () {
    $this->actingAs(verifiedUser())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/Profile'));
});

it('updates the profile name and email', function () {
    $user = verifiedUser(['name' => 'Old Name', 'email' => 'old@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'color' => '#112233',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

it('updates the profile colour', function () {
    $user = verifiedUser(['color' => '#3b82f6']);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'color' => '#EC4899'])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->color)->toBe('#EC4899');
});

it('rejects an invalid profile colour', function (?string $color) {
    $user = verifiedUser(['color' => '#3b82f6']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => $user->email, 'color' => $color])
        ->assertSessionHasErrors('color');

    expect($user->refresh()->color)->toBe('#3b82f6');
})->with([
    'missing' => [null],
    'named' => ['blue'],
    'short hex' => ['#fff'],
    'no hash' => ['3b82f6'],
]);

it('keeps email verification when the email is unchanged', function () {
    $user = verifiedUser(['email' => 'same@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Renamed',
            'email' => 'same@example.com',
            'color' => '#112233',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('validates the profile input', function () {
    $this->actingAs(verifiedUser())
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), ['name' => '', 'email' => 'not-an-email', 'color' => '#112233'])
        ->assertSessionHasErrors(['name', 'email']);
});

it('rejects an email already used by another user', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = verifiedUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), ['name' => 'Name', 'email' => 'taken@example.com', 'color' => '#112233'])
        ->assertSessionHasErrors('email');
});

it('deletes the account with the correct password', function () {
    $user = verifiedUser();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertModelMissing($user);
});

it('does not delete the account with a wrong password', function () {
    $user = verifiedUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    $this->assertModelExists($user);
});

it('blocks guests from deleting the account', function () {
    $this->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('login'));
});

it('deletes the user\'s finance records but keeps the other user\'s', function () {
    $user = verifiedUser();
    $other = verifiedUser();

    $asset = Asset::factory()->for($user, 'owner')->create();
    Statement::factory()->for($user, 'owner')->withAmount($asset, 100)->create();
    Expense::factory()->for($user, 'owner')->create();

    $otherAsset = Asset::factory()->for($other, 'owner')->create();
    $otherStatement = Statement::factory()->for($other, 'owner')->withAmount($otherAsset, 200)->create();
    $otherExpense = Expense::factory()->for($other, 'owner')->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect('/');

    expect(Asset::where('user_id', $user->id)->count())->toBe(0)
        ->and(Statement::where('user_id', $user->id)->count())->toBe(0)
        ->and(Expense::where('user_id', $user->id)->count())->toBe(0);

    $this->assertModelExists($otherAsset);
    $this->assertModelExists($otherStatement);
    $this->assertModelExists($otherExpense);
});

it('clears the other user\'s owner filter when it pointed at the deleted user', function () {
    $user = verifiedUser();
    $other = verifiedUser(['finance_owner_id' => $user->id]);
    $third = verifiedUser(['finance_owner_id' => $other->id]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password']);

    expect($other->refresh()->finance_owner_id)->toBeNull()
        ->and($third->refresh()->finance_owner_id)->toBe($other->id);
});
