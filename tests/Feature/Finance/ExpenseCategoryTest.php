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
