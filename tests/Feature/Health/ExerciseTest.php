<?php

use App\Models\Health\Exercise;
use App\Models\Health\ExerciseCategory;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('health.exercises.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('health.exercises.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('health/Exercise')
                ->has('exercises')
                ->has('meta')
                ->has('categories')
                ->has('filters')
                ->has('sort'));
    });
});

it('filters exercises by partial name', function () {
    Exercise::factory()->create(['name' => 'Bench Press']);
    Exercise::factory()->create(['name' => 'Squat']);

    $this->actingAs(verifiedUser())
        ->get(route('health.exercises.index', ['filter' => ['name' => 'Bench']]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('exercises', 1)
            ->where('exercises.0.name', 'Bench Press'));
});

it('filters exercises by multiple categories', function () {
    $mobility = ExerciseCategory::factory()->create(['name' => 'Mobility']);
    $grip = ExerciseCategory::factory()->create(['name' => 'Grip']);
    $sprint = ExerciseCategory::factory()->create(['name' => 'Sprint']);

    Exercise::factory()->create(['exercise_category_id' => $mobility->id]);
    Exercise::factory()->create(['exercise_category_id' => $grip->id]);
    Exercise::factory()->create(['exercise_category_id' => $sprint->id]);

    $this->actingAs(verifiedUser())
        ->get(route('health.exercises.index', ['filter' => ['exercise_category_id' => "{$mobility->id},{$grip->id}"]]))
        ->assertInertia(fn (Assert $page) => $page->has('exercises', 2));
});

it('sorts exercises by category name', function () {
    $zenith = ExerciseCategory::factory()->create(['name' => 'Zenith']);
    $apex = ExerciseCategory::factory()->create(['name' => 'Apex']);

    Exercise::factory()->create(['name' => 'Pullup', 'exercise_category_id' => $zenith->id]);
    Exercise::factory()->create(['name' => 'Curl', 'exercise_category_id' => $apex->id]);

    $this->actingAs(verifiedUser())
        ->get(route('health.exercises.index', ['sort' => 'category']))
        ->assertInertia(fn (Assert $page) => $page->where('exercises.0.exerciseCategory.name', 'Apex'));
});

it('rejects an invalid sort value', function () {
    $this->actingAs(verifiedUser())
        ->from(route('health.exercises.index'))
        ->get(route('health.exercises.index', ['sort' => 'bogus']))
        ->assertSessionHasErrors('sort');
});

it('stores an exercise', function () {
    $category = ExerciseCategory::factory()->create();

    $this->actingAs(verifiedUser())
        ->post(route('health.exercises.store'), [
            'exercise_category_id' => $category->id,
            'name' => 'Deadlift',
        ])
        ->assertSessionHas('success');

    $this->assertDatabaseHas('exercises', ['name' => 'Deadlift', 'exercise_category_id' => $category->id]);
});

it('validates exercise store input', function () {
    $this->actingAs(verifiedUser())
        ->from(route('health.exercises.index'))
        ->post(route('health.exercises.store'), ['exercise_category_id' => 999, 'name' => ''])
        ->assertSessionHasErrors(['exercise_category_id', 'name']);
});

it('rejects a duplicate exercise name', function () {
    $category = ExerciseCategory::factory()->create();
    Exercise::factory()->create(['name' => 'Plank']);

    $this->actingAs(verifiedUser())
        ->from(route('health.exercises.index'))
        ->post(route('health.exercises.store'), [
            'exercise_category_id' => $category->id,
            'name' => 'Plank',
        ])
        ->assertSessionHasErrors('name');
});

it('blocks guests from storing', function () {
    $category = ExerciseCategory::factory()->create();

    $this->post(route('health.exercises.store'), [
        'exercise_category_id' => $category->id,
        'name' => 'Deadlift',
    ])->assertRedirect(route('login'));

    expect(Exercise::count())->toBe(0);
});

it('updates an exercise', function () {
    $exercise = Exercise::factory()->create(['name' => 'Old']);
    $category = ExerciseCategory::factory()->create();

    $this->actingAs(verifiedUser())
        ->patch(route('health.exercises.update', $exercise), [
            'exercise_category_id' => $category->id,
            'name' => 'New',
        ])
        ->assertSessionHas('success');

    expect($exercise->refresh()->name)->toBe('New')
        ->and($exercise->exercise_category_id)->toBe($category->id);
});

it('deletes an exercise', function () {
    $exercise = Exercise::factory()->create();

    $this->actingAs(verifiedUser())
        ->delete(route('health.exercises.destroy', $exercise))
        ->assertSessionHas('success');

    $this->assertModelMissing($exercise);
});
