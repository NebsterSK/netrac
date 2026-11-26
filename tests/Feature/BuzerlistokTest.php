<?php

use App\Enums\Buzerlistok\MarkStatus;
use App\Models\Buzerlistok\Goal;
use App\Models\Buzerlistok\Mark;
use App\Models\Buzerlistok\Week;
use App\Models\Buzerlistok\WeeklyGoal;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('buzerlistok.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        Goal::factory()->create(['week_id' => $week->id, 'name' => 'Read']);
        WeeklyGoal::factory()->create(['week_id' => $week->id, 'name' => 'Gym', 'target' => 3, 'completions' => 1]);

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
                ->has('weeks.0.goals', 1)
                ->where('weeks.0.weekly_goals.0.name', 'Gym')
                ->where('weeks.0.weekly_goals.0.target', 3)
                ->where('weeks.0.weekly_goals.0.completions', 1));
    });
});

describe('day results for streaks', function () {
    it('rates each day of every week with daily goals, oldest first', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        $read = Goal::factory()->create(['week_id' => $week->id]);
        $run = Goal::factory()->create(['week_id' => $week->id]);

        Mark::factory()->create(['goal_id' => $read->id, 'marked_on' => '2026-07-06', 'status' => MarkStatus::Success]);
        Mark::factory()->create(['goal_id' => $run->id, 'marked_on' => '2026-07-06', 'status' => MarkStatus::Partial]);
        Mark::factory()->create(['goal_id' => $read->id, 'marked_on' => '2026-07-07', 'status' => MarkStatus::Success]);
        Mark::factory()->create(['goal_id' => $run->id, 'marked_on' => '2026-07-07', 'status' => MarkStatus::Fail]);
        Mark::factory()->create(['goal_id' => $read->id, 'marked_on' => '2026-07-08', 'status' => MarkStatus::Success]);

        $this->actingAs(verifiedUser())
            ->get(route('buzerlistok.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('dayResults', 7)
                ->where('dayResults.0', ['date' => '2026-07-06', 'result' => 'success'])
                ->where('dayResults.1', ['date' => '2026-07-07', 'result' => 'fail'])
                ->where('dayResults.2', ['date' => '2026-07-08', 'result' => 'incomplete'])
                ->where('dayResults.6', ['date' => '2026-07-12', 'result' => 'incomplete']));
    });

    it('leaves out weeks without daily goals', function () {
        Week::factory()->create(['starts_on' => '2026-06-29']);
        WeeklyGoal::factory()->create(['week_id' => Week::factory()->create(['starts_on' => '2026-07-06'])->id]);
        Goal::factory()->create(['week_id' => Week::factory()->create(['starts_on' => '2026-07-13'])->id]);

        $this->actingAs(verifiedUser())
            ->get(route('buzerlistok.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('dayResults', 7)
                ->where('dayResults.0.date', '2026-07-13'));
    });

    it('includes weeks beyond the twelve displayed ones', function () {
        foreach (range(0, 12) as $weeksAgo) {
            Goal::factory()->create([
                'week_id' => Week::factory()->create(['starts_on' => now()->startOfWeek()->subWeeks($weeksAgo)->toDateString()])->id,
            ]);
        }

        $this->actingAs(verifiedUser())
            ->get(route('buzerlistok.index'))
            ->assertInertia(fn (Assert $page) => $page->has('dayResults', 13 * 7));
    });
});

it('lists every taken week start, including weeks beyond the displayed ones', function () {
    foreach (range(0, 12) as $weeksAgo) {
        Week::factory()->create(['starts_on' => now()->startOfWeek()->subWeeks($weeksAgo)->toDateString()]);
    }

    $this->actingAs(verifiedUser())
        ->get(route('buzerlistok.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('weeks', 12)
            ->has('takenWeekStarts', 13)
            ->where('takenWeekStarts.0', now()->startOfWeek()->subWeeks(12)->toDateString()));
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
                'weekly_goals' => [],
            ])
            ->assertSessionHasErrors(['goals' => 'Add at least one daily or weekly goal.']);

        expect(Week::count())->toBe(0);
    });

    it('creates a week with weekly goals only', function () {
        $this->actingAs(verifiedUser())
            ->post(route('buzerlistok.store'), [
                'starts_on' => '2026-07-06',
                'goals' => [],
                'weekly_goals' => [
                    ['name' => 'Gym', 'target' => 3],
                    ['name' => 'Shave', 'target' => 1],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $week = Week::sole();

        expect($week->goals)->toHaveCount(0)
            ->and($week->weeklyGoals->pluck('name')->all())->toBe(['Gym', 'Shave'])
            ->and($week->weeklyGoals->pluck('target')->all())->toBe([3, 1])
            ->and($week->weeklyGoals->pluck('completions')->all())->toBe([0, 0])
            ->and($week->weeklyGoals->pluck('position')->all())->toBe([0, 1]);
    });

    it('rejects a weekly goal that is never required', function () {
        $this->actingAs(verifiedUser())
            ->post(route('buzerlistok.store'), [
                'starts_on' => '2026-07-06',
                'weekly_goals' => [['name' => 'Gym', 'target' => 0]],
            ])
            ->assertSessionHasErrors('weekly_goals.0.target');

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

    it('rejects the removed not-applicable status', function () {
        $goal = Goal::factory()->create();

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.goals.marks.update', $goal), [
                'marked_on' => '2026-07-06',
                'status' => 'na',
            ])
            ->assertSessionHasErrors('status');

        expect(Mark::count())->toBe(0);
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

    it('lists the other taken week starts but not its own', function () {
        Week::factory()->create(['starts_on' => '2026-06-29']);
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);

        $this->actingAs(verifiedUser())
            ->get(route('buzerlistok.edit', $week))
            ->assertInertia(fn (Assert $page) => $page
                ->where('takenWeekStarts', ['2026-06-29']));
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

    it('renames, retargets, adds and removes weekly goals while keeping completions', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        Goal::factory()->create(['week_id' => $week->id]);
        $gym = WeeklyGoal::factory()->create(['week_id' => $week->id, 'name' => 'Gym', 'target' => 3, 'completions' => 2]);
        $shave = WeeklyGoal::factory()->create(['week_id' => $week->id, 'name' => 'Shave', 'position' => 1]);

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.update', $week), [
                'starts_on' => '2026-07-06',
                'goals' => [['id' => $week->goals->first()->id, 'name' => 'Read']],
                'weekly_goals' => [
                    ['id' => null, 'name' => 'Call parents', 'target' => 1],
                    ['id' => $gym->id, 'name' => 'Workout', 'target' => 4],
                ],
            ])
            ->assertRedirect(route('buzerlistok.index'));

        $weeklyGoals = $week->fresh()->weeklyGoals;

        expect($weeklyGoals->pluck('name')->all())->toBe(['Call parents', 'Workout'])
            ->and($weeklyGoals[1]->id)->toBe($gym->id)
            ->and($weeklyGoals[1]->target)->toBe(4)
            ->and($weeklyGoals[1]->completions)->toBe(2)
            ->and(WeeklyGoal::find($shave->id))->toBeNull();
    });

    it('rejects weekly goals belonging to another week', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        $foreignWeeklyGoal = WeeklyGoal::factory()->create();

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.update', $week), [
                'starts_on' => '2026-07-06',
                'weekly_goals' => [['id' => $foreignWeeklyGoal->id, 'name' => 'Hijack', 'target' => 1]],
            ])
            ->assertSessionHasErrors('weekly_goals.0.id');

        expect($foreignWeeklyGoal->fresh()->name)->not->toBe('Hijack');
    });
});

describe('counting weekly goal completions', function () {
    it('redirects guests to login', function () {
        $weeklyGoal = WeeklyGoal::factory()->create();

        $this->put(route('buzerlistok.weekly-goals.completions.update', $weeklyGoal), ['completions' => 1])
            ->assertRedirect(route('login'));

        expect($weeklyGoal->fresh()->completions)->toBe(0);
    });

    it('sets how many times the goal was done', function () {
        $weeklyGoal = WeeklyGoal::factory()->create(['target' => 3]);

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.weekly-goals.completions.update', $weeklyGoal), ['completions' => 4])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect($weeklyGoal->fresh()->completions)->toBe(4);
    });

    it('rejects a negative count', function () {
        $weeklyGoal = WeeklyGoal::factory()->create(['completions' => 1]);

        $this->actingAs(verifiedUser())
            ->put(route('buzerlistok.weekly-goals.completions.update', $weeklyGoal), ['completions' => -1])
            ->assertSessionHasErrors('completions');

        expect($weeklyGoal->fresh()->completions)->toBe(1);
    });
});

describe('duplicating a week', function () {
    it('redirects guests to login', function () {
        $week = Week::factory()->create();

        $this->post(route('buzerlistok.duplicate', $week))
            ->assertRedirect(route('login'));

        expect(Week::count())->toBe(1);
    });

    it('copies the goals into the following week without marks', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        $read = Goal::factory()->create(['week_id' => $week->id, 'name' => 'Read', 'position' => 0]);
        Goal::factory()->create(['week_id' => $week->id, 'name' => 'Exercise', 'position' => 1]);
        Mark::factory()->create(['goal_id' => $read->id, 'marked_on' => '2026-07-06']);

        $this->actingAs(verifiedUser())
            ->post(route('buzerlistok.duplicate', $week))
            ->assertRedirect()
            ->assertSessionHas('success');

        $duplicate = Week::whereDate('starts_on', '2026-07-13')->sole();

        expect($duplicate->goals->pluck('name')->all())->toBe(['Read', 'Exercise'])
            ->and($duplicate->goals->pluck('position')->all())->toBe([0, 1])
            ->and(Mark::whereIn('goal_id', $duplicate->goals->pluck('id'))->count())->toBe(0)
            ->and($week->fresh()->goals)->toHaveCount(2);
    });

    it('copies the weekly goals without their completions', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        WeeklyGoal::factory()->create(['week_id' => $week->id, 'name' => 'Gym', 'target' => 3, 'completions' => 2]);

        $this->actingAs(verifiedUser())
            ->post(route('buzerlistok.duplicate', $week))
            ->assertSessionHas('success');

        $duplicate = Week::whereDate('starts_on', '2026-07-13')->sole();

        expect($duplicate->weeklyGoals)->toHaveCount(1)
            ->and($duplicate->weeklyGoals->first()->only(['name', 'target', 'completions']))
            ->toBe(['name' => 'Gym', 'target' => 3, 'completions' => 0]);
    });

    it('refuses to duplicate a week that is not the latest', function () {
        $week = Week::factory()->create(['starts_on' => '2026-07-06']);
        Goal::factory()->create(['week_id' => $week->id]);
        Week::factory()->create(['starts_on' => '2026-07-20']);

        $this->actingAs(verifiedUser())
            ->post(route('buzerlistok.duplicate', $week))
            ->assertRedirect()
            ->assertSessionHas('error', 'Only the latest buzerlistok can be duplicated.');

        expect(Week::count())->toBe(2)
            ->and(Goal::count())->toBe(1);
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
