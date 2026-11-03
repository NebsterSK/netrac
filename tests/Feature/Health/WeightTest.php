<?php

use App\Models\Health\Weight;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('health.weights.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('health.weights.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('health/Weight')->has('weights'));
    });
});

it('lists weights newest first', function () {
    Weight::factory()->create(['date' => '2026-01-10', 'weight' => 82.4]);
    Weight::factory()->create(['date' => '2026-03-02', 'weight' => 80.1]);

    $this->actingAs(verifiedUser())
        ->get(route('health.weights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('weights', 2)
            ->where('weights.0.date', '2026-03-02')
            ->where('weights.0.weight', 80.1)
            ->where('weights.1.date', '2026-01-10'));
});

it('stores a weight', function () {
    $this->actingAs(verifiedUser())
        ->post(route('health.weights.store'), ['date' => '2026-11-03', 'weight' => 81.3])
        ->assertRedirect()
        ->assertSessionHas('success');

    $weight = Weight::sole();

    expect($weight->date->toDateString())->toBe('2026-11-03')
        ->and((float) $weight->weight)->toBe(81.3);
});

it('rejects a second weight on the same date', function () {
    Weight::factory()->create(['date' => '2026-11-03']);

    $this->actingAs(verifiedUser())
        ->post(route('health.weights.store'), ['date' => '2026-11-03', 'weight' => 81.3])
        ->assertSessionHasErrors('date');

    expect(Weight::count())->toBe(1);
});

it('rejects an invalid weight', function (mixed $weight) {
    $this->actingAs(verifiedUser())
        ->post(route('health.weights.store'), ['date' => '2026-11-03', 'weight' => $weight])
        ->assertSessionHasErrors('weight');
})->with([
    'missing' => null,
    'zero' => 0,
    'too many decimals' => 80.25,
    'too heavy' => 1000,
]);

it('updates a weight, keeping its own date allowed', function () {
    $weight = Weight::factory()->create(['date' => '2026-11-03', 'weight' => 80.0]);

    $this->actingAs(verifiedUser())
        ->put(route('health.weights.update', $weight), ['date' => '2026-11-03', 'weight' => 79.5])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect((float) $weight->fresh()->weight)->toBe(79.5);
});

it("rejects moving a weight onto another entry's date", function () {
    Weight::factory()->create(['date' => '2026-11-01']);
    $weight = Weight::factory()->create(['date' => '2026-11-03']);

    $this->actingAs(verifiedUser())
        ->put(route('health.weights.update', $weight), ['date' => '2026-11-01', 'weight' => 79.5])
        ->assertSessionHasErrors('date');
});

it('deletes a weight', function () {
    $weight = Weight::factory()->create();

    $this->actingAs(verifiedUser())
        ->delete(route('health.weights.destroy', $weight))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertModelMissing($weight);
});
