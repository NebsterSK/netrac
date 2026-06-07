<?php

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
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

it('keeps email verification when the email is unchanged', function () {
    $user = verifiedUser(['email' => 'same@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Renamed',
            'email' => 'same@example.com',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('validates the profile input', function () {
    $this->actingAs(verifiedUser())
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), ['name' => '', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);
});

it('rejects an email already used by another user', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = verifiedUser();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), ['name' => 'Name', 'email' => 'taken@example.com'])
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
