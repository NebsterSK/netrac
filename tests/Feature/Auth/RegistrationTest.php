<?php

it('has no registration page', function () {
    $this->get('/register')->assertNotFound();
});

it('does not accept registrations', function () {
    $this->post('/register', [
        'name' => 'Intruder',
        'email' => 'intruder@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(404);

    $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
});
