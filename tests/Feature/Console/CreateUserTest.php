<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a verified user with the given colour', function () {
    $this->artisan('user:create', ['name' => 'Lucka', 'email' => 'lucka@example.com', '--color' => '#ec4899'])
        ->expectsQuestion('Password', 'a-Strong-Passw0rd!')
        ->expectsQuestion('Confirm password', 'a-Strong-Passw0rd!')
        ->expectsOutputToContain('User created with id')
        ->assertSuccessful();

    $user = User::where('email', 'lucka@example.com')->firstOrFail();

    expect($user->name)->toBe('Lucka')
        ->and($user->color)->toBe('#ec4899')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('a-Strong-Passw0rd!', $user->password))->toBeTrue();
});

it('defaults the colour to grey', function () {
    $this->artisan('user:create', ['name' => 'Grey', 'email' => 'grey@example.com'])
        ->expectsQuestion('Password', 'a-Strong-Passw0rd!')
        ->expectsQuestion('Confirm password', 'a-Strong-Passw0rd!')
        ->assertSuccessful();

    expect(User::where('email', 'grey@example.com')->value('color'))->toBe('#6b7280');
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('user:create', ['name' => 'Dupe', 'email' => 'taken@example.com'])
        ->expectsQuestion('Password', 'a-Strong-Passw0rd!')
        ->expectsQuestion('Confirm password', 'a-Strong-Passw0rd!')
        ->assertFailed();

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

it('rejects an invalid colour', function () {
    $this->artisan('user:create', ['name' => 'Bad', 'email' => 'bad@example.com', '--color' => 'blue'])
        ->expectsQuestion('Password', 'a-Strong-Passw0rd!')
        ->expectsQuestion('Confirm password', 'a-Strong-Passw0rd!')
        ->assertFailed();

    $this->assertDatabaseMissing('users', ['email' => 'bad@example.com']);
});

it('rejects mismatching passwords', function () {
    $this->artisan('user:create', ['name' => 'Bad', 'email' => 'bad@example.com'])
        ->expectsQuestion('Password', 'a-Strong-Passw0rd!')
        ->expectsQuestion('Confirm password', 'different')
        ->assertFailed();

    $this->assertDatabaseMissing('users', ['email' => 'bad@example.com']);
});
