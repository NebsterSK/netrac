<?php

use App\Models\Health\Exercise;
use App\Models\Health\WorkoutSession;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('health.sessions.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('health.sessions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('health/sessions/Index')->has('sessions'));
    });
});

it('shows the create page with exercises', function () {
    Exercise::factory()->count(2)->create();

    $this->actingAs(verifiedUser())
        ->get(route('health.sessions.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('health/sessions/Create')
            ->has('exercises', 2));
});

it('stores a session with ordered exercises', function () {
    $exercises = Exercise::factory()->count(3)->create();

    $this->actingAs(verifiedUser())
        ->post(route('health.sessions.store'), ['exercise_ids' => $exercises->pluck('id')->all()])
        ->assertSessionHas('success');

    $session = WorkoutSession::sole();

    expect($session->exercises)->toHaveCount(3);

    $this->assertDatabaseHas('exercise_workout_session', [
        'workout_session_id' => $session->id,
        'exercise_id' => $exercises->first()->id,
        'position' => 0,
        'completed' => false,
    ]);
});

it('redirects to the new session after store', function () {
    $exercise = Exercise::factory()->create();

    $this->actingAs(verifiedUser())
        ->post(route('health.sessions.store'), ['exercise_ids' => [$exercise->id]])
        ->assertRedirect(route('health.sessions.show', WorkoutSession::sole()));
});

it('requires at least one exercise', function () {
    $this->actingAs(verifiedUser())
        ->from(route('health.sessions.create'))
        ->post(route('health.sessions.store'), ['exercise_ids' => []])
        ->assertSessionHasErrors('exercise_ids');

    expect(WorkoutSession::count())->toBe(0);
});

it('rejects duplicate or unknown exercise ids', function () {
    $exercise = Exercise::factory()->create();

    $this->actingAs(verifiedUser())
        ->from(route('health.sessions.create'))
        ->post(route('health.sessions.store'), ['exercise_ids' => [$exercise->id, $exercise->id]])
        ->assertSessionHasErrors('exercise_ids.1');
});

it('blocks guests from storing', function () {
    $exercise = Exercise::factory()->create();

    $this->post(route('health.sessions.store'), ['exercise_ids' => [$exercise->id]])
        ->assertRedirect(route('login'));

    expect(WorkoutSession::count())->toBe(0);
});

it('shows a session', function () {
    $session = WorkoutSession::factory()->create();
    $session->exercises()->attach(Exercise::factory()->create()->id, ['position' => 0, 'completed' => false]);

    $this->actingAs(verifiedUser())
        ->get(route('health.sessions.show', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('health/sessions/Show')->has('session'));
});

it('toggles an exercise completion flag', function () {
    $session = WorkoutSession::factory()->create();
    $exercise = Exercise::factory()->create();
    $session->exercises()->attach($exercise->id, ['position' => 0, 'completed' => false]);

    $this->actingAs(verifiedUser())
        ->from(route('health.sessions.show', $session))
        ->patch(route('health.sessions.exercises.update', [$session, $exercise]), ['completed' => true])
        ->assertRedirect(route('health.sessions.show', $session));

    $this->assertDatabaseHas('exercise_workout_session', [
        'workout_session_id' => $session->id,
        'exercise_id' => $exercise->id,
        'completed' => true,
    ]);
});

it('validates the completed flag', function () {
    $session = WorkoutSession::factory()->create();
    $exercise = Exercise::factory()->create();
    $session->exercises()->attach($exercise->id, ['position' => 0, 'completed' => false]);

    $this->actingAs(verifiedUser())
        ->from(route('health.sessions.show', $session))
        ->patch(route('health.sessions.exercises.update', [$session, $exercise]), [])
        ->assertSessionHasErrors('completed');
});

it('deletes a session', function () {
    $session = WorkoutSession::factory()->create();

    $this->actingAs(verifiedUser())
        ->delete(route('health.sessions.destroy', $session))
        ->assertRedirect(route('health.sessions.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($session);
});
