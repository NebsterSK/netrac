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
