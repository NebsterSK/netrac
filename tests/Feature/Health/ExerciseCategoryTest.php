<?php

use App\Models\Health\ExerciseCategory;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('health.exercise-categories.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('health.exercise-categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('health/ExerciseCategory')->has('categories'));
    });
});

it('orders categories by priority then name', function () {
    ExerciseCategory::query()->delete();
    ExerciseCategory::factory()->create(['name' => 'Cardio', 'priority' => 2]);
    ExerciseCategory::factory()->create(['name' => 'Arms', 'priority' => 1]);

    $this->actingAs(verifiedUser())
        ->get(route('health.exercise-categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 2)
            ->where('categories.0.name', 'Arms'));
});

it('stores the first category with priority 1 when none exist', function () {
    ExerciseCategory::query()->delete();

    $this->actingAs(verifiedUser())
        ->post(route('health.exercise-categories.store'), ['name' => 'Shoulders'])
        ->assertRedirect(route('health.exercise-categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('exercise_categories', ['name' => 'Shoulders', 'priority' => 1]);
});

it('stores a new category at the current max priority', function () {
    $maxPriority = ExerciseCategory::max('priority');

    $this->actingAs(verifiedUser())
        ->post(route('health.exercise-categories.store'), ['name' => 'Mobility'])
        ->assertSessionHas('success');

    $this->assertDatabaseHas('exercise_categories', ['name' => 'Mobility', 'priority' => $maxPriority]);
});

it('requires a unique name on store', function () {
    $existing = ExerciseCategory::firstOrFail();

    $this->actingAs(verifiedUser())
        ->from(route('health.exercise-categories.index'))
        ->post(route('health.exercise-categories.store'), ['name' => $existing->name])
        ->assertSessionHasErrors('name');
});

it('blocks guests from storing', function () {
    $baseline = ExerciseCategory::count();

    $this->post(route('health.exercise-categories.store'), ['name' => 'Shoulders'])
        ->assertRedirect(route('login'));

    expect(ExerciseCategory::count())->toBe($baseline);
});

it('renames a category', function () {
    $category = ExerciseCategory::factory()->create(['name' => 'Old']);

    $this->actingAs(verifiedUser())
        ->patch(route('health.exercise-categories.update', $category), ['name' => 'New'])
        ->assertSessionHas('success');

    expect($category->refresh()->name)->toBe('New');
});

it('updates a category priority', function () {
    $category = ExerciseCategory::factory()->create(['priority' => 1]);

    $this->actingAs(verifiedUser())
        ->from(route('health.exercise-categories.index'))
        ->patch(route('health.exercise-categories.priority.update', $category), ['priority' => 3])
        ->assertRedirect(route('health.exercise-categories.index'));

    expect($category->refresh()->priority)->toBe(3);
});

it('validates the priority value', function () {
    $category = ExerciseCategory::factory()->create();

    $this->actingAs(verifiedUser())
        ->from(route('health.exercise-categories.index'))
        ->patch(route('health.exercise-categories.priority.update', $category), ['priority' => 0])
        ->assertSessionHasErrors('priority');
});

it('deletes a category', function () {
    $category = ExerciseCategory::factory()->create();

    $this->actingAs(verifiedUser())
        ->delete(route('health.exercise-categories.destroy', $category))
        ->assertSessionHas('success');

    $this->assertModelMissing($category);
});
