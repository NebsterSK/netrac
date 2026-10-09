<?php

use App\Models\Finance\Expense;
use App\Models\Finance\ExpenseCategory;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('finance.expense-categories.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('finance.expense-categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('finance/ExpenseCategory')
                ->has('categories'));
    });
});

it('lists categories alphabetically with expense counts', function () {
    $food = ExpenseCategory::factory()->create(['name' => 'Food']);
    ExpenseCategory::factory()->create(['name' => 'Apex']);

    Expense::factory()->count(2)->create(['expense_category_id' => $food->id]);

    $this->actingAs(verifiedUser())
        ->get(route('finance.expense-categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 2)
            ->where('categories.0.name', 'Apex')
            ->where('categories.1.name', 'Food')
            ->where('categories.1.expenses_count', 2));
});

it('stores a category', function () {
    $this->actingAs(verifiedUser())
        ->post(route('finance.expense-categories.store'), ['name' => 'Utilities'])
        ->assertRedirect(route('finance.expense-categories.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expense_categories', ['name' => 'Utilities']);
});

it('rejects a duplicate category name', function () {
    ExpenseCategory::factory()->create(['name' => 'Rent']);

    $this->actingAs(verifiedUser())
        ->from(route('finance.expense-categories.index'))
        ->post(route('finance.expense-categories.store'), ['name' => 'Rent'])
        ->assertSessionHasErrors('name');
});

it('validates category store input', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.expense-categories.index'))
        ->post(route('finance.expense-categories.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('blocks guests from storing', function () {
    $this->post(route('finance.expense-categories.store'), ['name' => 'Utilities'])
        ->assertRedirect(route('login'));

    expect(ExpenseCategory::count())->toBe(0);
});

it('updates a category', function () {
    $category = ExpenseCategory::factory()->create(['name' => 'Old']);

    $this->actingAs(verifiedUser())
        ->patch(route('finance.expense-categories.update', $category), ['name' => 'New'])
        ->assertRedirect(route('finance.expense-categories.index'))
        ->assertSessionHas('success');

    expect($category->refresh()->name)->toBe('New');
});

it('keeps the same name on update', function () {
    $category = ExpenseCategory::factory()->create(['name' => 'Groceries']);

    $this->actingAs(verifiedUser())
        ->patch(route('finance.expense-categories.update', $category), ['name' => 'Groceries'])
        ->assertSessionHasNoErrors();
});

it('deletes a category and cascades its expenses', function () {
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create(['expense_category_id' => $category->id]);

    $this->actingAs(verifiedUser())
        ->delete(route('finance.expense-categories.destroy', $category))
        ->assertRedirect(route('finance.expense-categories.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($category);
    $this->assertModelMissing($expense);
});

describe('reorder', function () {
    it('blocks guests from reordering', function () {
        $category = ExpenseCategory::factory()->create();

        $this->put(route('finance.expense-categories.reorder'), ['ids' => [$category->id]])
            ->assertRedirect(route('login'));
    });

    it('saves the given order as positions', function () {
        $first = ExpenseCategory::factory()->create(['position' => 1]);
        $second = ExpenseCategory::factory()->create(['position' => 2]);
        $third = ExpenseCategory::factory()->create(['position' => 3]);

        $this->actingAs(verifiedUser())
            ->from(route('dashboard'))
            ->put(route('finance.expense-categories.reorder'), ['ids' => [$third->id, $first->id, $second->id]])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        expect($third->refresh()->position)->toBe(1)
            ->and($first->refresh()->position)->toBe(2)
            ->and($second->refresh()->position)->toBe(3);
    });

    it('rejects unknown or duplicate ids', function () {
        $category = ExpenseCategory::factory()->create();

        $this->actingAs(verifiedUser())
            ->from(route('dashboard'))
            ->put(route('finance.expense-categories.reorder'), ['ids' => [$category->id, $category->id, 999999]])
            ->assertSessionHasErrors(['ids.0', 'ids.2']);
    });
});

it('appends a new category after the last position', function () {
    ExpenseCategory::factory()->create(['position' => 4]);

    $this->actingAs(verifiedUser())
        ->post(route('finance.expense-categories.store'), ['name' => 'Travel']);

    expect(ExpenseCategory::where('name', 'Travel')->value('position'))->toBe(5);
});

describe('color', function () {
    it('stores a category color', function () {
        $this->actingAs(verifiedUser())
            ->post(route('finance.expense-categories.store'), ['name' => 'Travel', 'color' => '#3b82f6'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expense_categories', ['name' => 'Travel', 'color' => '#3b82f6']);
    });

    it('clears a category color on update', function () {
        $category = ExpenseCategory::factory()->create(['color' => '#ef4444']);

        $this->actingAs(verifiedUser())
            ->put(route('finance.expense-categories.update', $category), ['name' => $category->name, 'color' => null])
            ->assertSessionHasNoErrors();

        expect($category->refresh()->color)->toBeNull();
    });

    it('rejects an invalid category color', function (string $color) {
        $this->actingAs(verifiedUser())
            ->from(route('finance.expense-categories.index'))
            ->post(route('finance.expense-categories.store'), ['name' => 'Travel', 'color' => $color])
            ->assertSessionHasErrors('color');
    })->with(['red', '#fff', '#12345g', '3b82f6']);
});
