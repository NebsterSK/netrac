<?php

use App\Enums\Buzerlistok\MarkStatus;
use App\Models\Buzerlistok\Goal;
use App\Models\Buzerlistok\Mark;
use App\Models\Buzerlistok\Week;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('buzerlistok.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        Goal::factory()->create(['week_id' => $week->id, 'name' => 'Read']);

        $this->actingAs(verifiedUser())
            ->get(route('buzerlistok.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('buzerlistok/Index')
                ->has('weeks', 1)
                ->where('weeks.0.starts_on', '2026-07-06')
                ->has('weeks.0.days', 7)
                ->where('weeks.0.days.0', '2026-07-06')
                ->where('weeks.0.days.6', '2026-07-12')
                ->has('weeks.0.goals', 1));
    });
});

describe('creating a week', function () {
    it('creates a week with goals and snaps the date to Monday', function () {
        $this->actingAs(verifiedUser())
            ->post(route('buzerlistok.store'), [
                'starts_on' => '2026-07-09', // a Thursday
                'goals' => ['Exercise', 'Read', 'No sugar'],
            ])
            ->assertRedirect();

        $week = Week::sole();

        expect($week->starts_on->toDateString())->toBe('2026-07-06');
        expect($week->goals)->toHaveCount(3);
        expect($week->goals->pluck('name')->all())->toBe(['Exercise', 'Read', 'No sugar']);
        expect($week->goals->pluck('position')->all())->toBe([0, 1, 2]);
    });

    it('requires at least one goal', function () {
        $this->actingAs(verifiedUser())
            ->post(route('buzerlistok.store'), [
                'starts_on' => '2026-07-06',
                'goals' => [],
            ])
            ->assertSessionHasErrors('goals');

        expect(Week::count())->toBe(0);
    });

    it('rejects a second week in the same calendar week', function () {
        Week::factory()->create(['starts_on' => '2026-07-06']);

        $this->actingAs(verifiedUser())
            ->post(route('buzerlistok.store'), [
                'starts_on' => '2026-07-08', // snaps to 2026-07-06
                'goals' => ['Read'],
            ])
            ->assertSessionHasErrors('starts_on');

        expect(Week::count())->toBe(1);
    });
});

describe('painting marks', function () {
    it('creates a mark for a cell', function () {
        $goal = Goal::factory()->create();

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.goals.marks.update', $goal), [
                'marked_on' => '2026-07-06',
                'status' => MarkStatus::Success->value,
            ])
            ->assertRedirect();

        $mark = Mark::sole();

        expect($mark->goal_id)->toBe($goal->id);
        expect($mark->status)->toBe(MarkStatus::Success);
        expect($mark->marked_on->toDateString())->toBe('2026-07-06');
    });

    it('overwrites an existing mark for the same day', function () {
        $goal = Goal::factory()->create();
        Mark::factory()->create([
            'goal_id' => $goal->id,
            'marked_on' => '2026-07-06',
            'status' => MarkStatus::Success,
        ]);

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.goals.marks.update', $goal), [
                'marked_on' => '2026-07-06',
                'status' => MarkStatus::Fail->value,
            ]);

        expect(Mark::count())->toBe(1);
        expect(Mark::sole()->status)->toBe(MarkStatus::Fail);
    });

    it('requires a comment for the not-applicable status', function () {
        $goal = Goal::factory()->create();

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.goals.marks.update', $goal), [
                'marked_on' => '2026-07-06',
                'status' => MarkStatus::NotApplicable->value,
            ])
            ->assertSessionHasErrors('comment');

        expect(Mark::count())->toBe(0);
    });

    it('stores a comment with the not-applicable status', function () {
        $goal = Goal::factory()->create();

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.goals.marks.update', $goal), [
                'marked_on' => '2026-07-06',
                'status' => MarkStatus::NotApplicable->value,
                'comment' => 'Rest day',
            ]);

        $mark = Mark::sole();

        expect($mark->status)->toBe(MarkStatus::NotApplicable);
        expect($mark->comment)->toBe('Rest day');
    });

    it('erases a mark when the status is null', function () {
        $goal = Goal::factory()->create();
        Mark::factory()->create([
            'goal_id' => $goal->id,
            'marked_on' => '2026-07-06',
        ]);

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.goals.marks.update', $goal), [
                'marked_on' => '2026-07-06',
                'status' => null,
            ])
            ->assertRedirect();

        expect(Mark::count())->toBe(0);
    });
});

describe('editing a week', function () {
    it('redirects guests away from the edit page', function () {
        $week = Week::factory()->create();

        $this->get(route('buzerlistok.edit', $week))
            ->assertRedirect(route('login'));
    });

    it('redirects guests away from updating', function () {
        $week = Week::factory()->create();

        $this->put(route('buzerlistok.update', $week), [
            'starts_on' => '2026-07-06',
            'goals' => [['id' => null, 'name' => 'Read']],
        ])->assertRedirect(route('login'));

        expect(Goal::count())->toBe(0);
    });

    it('renders the edit page', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        Goal::factory()->create(['week_id' => $week->id, 'name' => 'Read']);

        $this->actingAs(verifiedUser())
            ->get(route('buzerlistok.edit', $week))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('buzerlistok/Edit')
                ->where('week.id', $week->id)
                ->where('week.starts_on', '2026-07-06')
                ->where('week.goals.0.name', 'Read'));
    });

    it('renames, reorders, adds and removes goals', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        $read = Goal::factory()->create(['week_id' => $week->id, 'name' => 'Read', 'position' => 0]);
        $exercise = Goal::factory()->create(['week_id' => $week->id, 'name' => 'Exercise', 'position' => 1]);
        $sugar = Goal::factory()->create(['week_id' => $week->id, 'name' => 'No sugar', 'position' => 2]);
        Mark::factory()->create(['goal_id' => $read->id, 'marked_on' => '2026-07-06']);
        Mark::factory()->create(['goal_id' => $sugar->id, 'marked_on' => '2026-07-06']);

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.update', $week), [
                'starts_on' => '2026-07-06',
                'goals' => [
                    ['id' => $exercise->id, 'name' => 'Workout'],
                    ['id' => $read->id, 'name' => 'Read'],
                    ['id' => null, 'name' => 'Meditate'],
                ],
            ])
            ->assertRedirect(route('buzerlistok.index'));

        $goals = $week->fresh()->goals;

        expect($goals->pluck('name')->all())->toBe(['Workout', 'Read', 'Meditate']);
        expect($goals->pluck('position')->all())->toBe([0, 1, 2]);
        expect($goals[0]->id)->toBe($exercise->id);
        expect($goals[1]->id)->toBe($read->id);
        expect(Goal::find($sugar->id))->toBeNull();
        expect(Mark::count())->toBe(1);
        expect(Mark::sole()->goal_id)->toBe($read->id);
    });

    it('moves the week and shifts its marks along', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        $goal = Goal::factory()->create(['week_id' => $week->id]);
        Mark::factory()->create(['goal_id' => $goal->id, 'marked_on' => '2026-07-08']);

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.update', $week), [
                'starts_on' => '2026-07-15', // a Wednesday, snaps to 2026-07-13
                'goals' => [['id' => $goal->id, 'name' => $goal->name]],
            ])
            ->assertRedirect();

        expect($week->fresh()->starts_on->toDateString())->toBe('2026-07-13');
        expect(Mark::sole()->marked_on->toDateString())->toBe('2026-07-15');
    });

    it('allows keeping the same week start', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        $goal = Goal::factory()->create(['week_id' => $week->id]);

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.update', $week), [
                'starts_on' => '2026-07-06',
                'goals' => [['id' => $goal->id, 'name' => 'Renamed']],
            ])
            ->assertSessionHasNoErrors();

        expect($goal->fresh()->name)->toBe('Renamed');
    });

    it('rejects moving onto a week that already exists', function () {
        Week::factory()->create(['starts_on' => '2026-07-13']);
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        $goal = Goal::factory()->create(['week_id' => $week->id]);

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.update', $week), [
                'starts_on' => '2026-07-14',
                'goals' => [['id' => $goal->id, 'name' => $goal->name]],
            ])
            ->assertSessionHasErrors('starts_on');

        expect($week->fresh()->starts_on->toDateString())->toBe('2026-07-06');
    });

    it('rejects goals belonging to another week', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        $foreignGoal = Goal::factory()->create();

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.update', $week), [
                'starts_on' => '2026-07-06',
                'goals' => [['id' => $foreignGoal->id, 'name' => 'Hijack']],
            ])
            ->assertSessionHasErrors('goals.0.id');

        expect($foreignGoal->fresh()->name)->not->toBe('Hijack');
    });
});

describe('deleting a week', function () {
    it('deletes the week and cascades its goals and marks', function () {
        $week = Week::factory()->create();
        $goal = Goal::factory()->create(['week_id' => $week->id]);
        Mark::factory()->create(['goal_id' => $goal->id]);

        $this->actingAs(verifiedUser())
            ->delete(route('buzerlistok.destroy', $week))
            ->assertRedirect();

        expect(Week::count())->toBe(0);
        expect(Goal::count())->toBe(0);
        expect(Mark::count())->toBe(0);
    });
});
