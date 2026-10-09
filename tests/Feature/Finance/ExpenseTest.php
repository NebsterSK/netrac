<?php

use App\Models\Finance\Expense;
use App\Models\Finance\ExpenseCategory;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('finance.expenses.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('finance.expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('finance/Expense')
                ->has('expenses')
                ->has('meta')
                ->has('categories')
                ->has('filters')
                ->has('sort'));
    });
});

it('filters expenses by partial name', function () {
    Expense::factory()->create(['name' => 'Weekly Groceries']);
    Expense::factory()->create(['name' => 'Gym Membership']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.expenses.index', ['filter' => ['name' => 'Groc']]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('expenses', 1)
            ->where('expenses.0.name', 'Weekly Groceries'));
});

it('filters expenses by multiple categories', function () {
    $rent = ExpenseCategory::factory()->create(['name' => 'Rent']);
    $food = ExpenseCategory::factory()->create(['name' => 'Food']);
    $travel = ExpenseCategory::factory()->create(['name' => 'Travel']);

    Expense::factory()->create(['expense_category_id' => $rent->id]);
    Expense::factory()->create(['expense_category_id' => $food->id]);
    Expense::factory()->create(['expense_category_id' => $travel->id]);

    $this->actingAs(verifiedUser())
        ->get(route('finance.expenses.index', ['filter' => ['expense_category_id' => "{$rent->id},{$food->id}"]]))
        ->assertInertia(fn (Assert $page) => $page->has('expenses', 2));
});

it('sorts expenses by amount', function () {
    Expense::factory()->create(['name' => 'Cheap', 'amount' => 10]);
    Expense::factory()->create(['name' => 'Pricey', 'amount' => 900]);

    $this->actingAs(verifiedUser())
        ->get(route('finance.expenses.index', ['sort' => '-amount']))
        ->assertInertia(fn (Assert $page) => $page->where('expenses.0.name', 'Pricey'));
});

it('sorts expenses by category name', function () {
    $zenith = ExpenseCategory::factory()->create(['name' => 'Zenith']);
    $apex = ExpenseCategory::factory()->create(['name' => 'Apex']);

    Expense::factory()->create(['name' => 'B', 'expense_category_id' => $zenith->id]);
    Expense::factory()->create(['name' => 'A', 'expense_category_id' => $apex->id]);

    $this->actingAs(verifiedUser())
        ->get(route('finance.expenses.index', ['sort' => 'category']))
        ->assertInertia(fn (Assert $page) => $page->where('expenses.0.expenseCategory.name', 'Apex'));
});

it('rejects an invalid sort value', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.expenses.index'))
        ->get(route('finance.expenses.index', ['sort' => 'bogus']))
        ->assertSessionHasErrors('sort');
});

it('stores an expense', function () {
    $category = ExpenseCategory::factory()->create();

    $this->actingAs(verifiedUser())
        ->post(route('finance.expenses.store'), [
            'expense_category_id' => $category->id,
            'name' => 'Electricity',
            'amount' => 120,
        ])
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'name' => 'Electricity',
        'expense_category_id' => $category->id,
        'amount' => 120,
    ]);
});

it('validates expense store input', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.expenses.index'))
        ->post(route('finance.expenses.store'), ['expense_category_id' => 999, 'name' => '', 'amount' => 'abc'])
        ->assertSessionHasErrors(['expense_category_id', 'name', 'amount']);
});

it('rejects a duplicate expense name', function () {
    $category = ExpenseCategory::factory()->create();
    Expense::factory()->create(['name' => 'Netflix']);

    $this->actingAs(verifiedUser())
        ->from(route('finance.expenses.index'))
        ->post(route('finance.expenses.store'), [
            'expense_category_id' => $category->id,
            'name' => 'Netflix',
            'amount' => 13,
        ])
        ->assertSessionHasErrors('name');
});

it('sorts by amount descending by default', function () {
    Expense::factory()->create(['name' => 'Spotify', 'amount' => 11]);
    Expense::factory()->create(['name' => 'Rent', 'amount' => 1200]);

    $this->actingAs(verifiedUser())
        ->get(route('finance.expenses.index'))
        ->assertInertia(fn (Assert $page) => $page->where('expenses.0.name', 'Rent'));
});

it('rejects a negative amount', function () {
    $category = ExpenseCategory::factory()->create();

    $this->actingAs(verifiedUser())
        ->from(route('finance.expenses.index'))
        ->post(route('finance.expenses.store'), [
            'expense_category_id' => $category->id,
            'name' => 'Refund',
            'amount' => -50,
        ])
        ->assertSessionHasErrors('amount');
});

it('blocks guests from storing', function () {
    $category = ExpenseCategory::factory()->create();

    $this->post(route('finance.expenses.store'), [
        'expense_category_id' => $category->id,
        'name' => 'Electricity',
        'amount' => 120,
    ])->assertRedirect(route('login'));

    expect(Expense::count())->toBe(0);
});

it('updates an expense', function () {
    $expense = Expense::factory()->create(['name' => 'Old', 'amount' => 50]);
    $category = ExpenseCategory::factory()->create();

    $this->actingAs(verifiedUser())
        ->patch(route('finance.expenses.update', $expense), [
            'expense_category_id' => $category->id,
            'name' => 'New',
            'amount' => 75,
        ])
        ->assertSessionHas('success');

    expect($expense->refresh()->name)->toBe('New')
        ->and($expense->expense_category_id)->toBe($category->id)
        ->and($expense->amount)->toBe(75);
});

it('deletes an expense', function () {
    $expense = Expense::factory()->create();

    $this->actingAs(verifiedUser())
        ->delete(route('finance.expenses.destroy', $expense))
        ->assertSessionHas('success');

    $this->assertModelMissing($expense);
});

describe('color', function () {
    it('stores an expense color', function () {
        $category = ExpenseCategory::factory()->create();

        $this->actingAs(verifiedUser())
            ->post(route('finance.expenses.store'), [
                'expense_category_id' => $category->id,
                'name' => 'Netflix',
                'amount' => 14,
                'color' => '#e50914',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', ['name' => 'Netflix', 'color' => '#e50914']);
    });

    it('updates an expense color', function () {
        $expense = Expense::factory()->create();

        $this->actingAs(verifiedUser())
            ->put(route('finance.expenses.update', $expense), [
                'expense_category_id' => $expense->expense_category_id,
                'name' => $expense->name,
                'amount' => $expense->amount,
                'color' => '#22c55e',
            ])
            ->assertSessionHasNoErrors();

        expect($expense->refresh()->color)->toBe('#22c55e');
    });

    it('rejects an invalid expense color', function () {
        $category = ExpenseCategory::factory()->create();

        $this->actingAs(verifiedUser())
            ->from(route('finance.expenses.index'))
            ->post(route('finance.expenses.store'), [
                'expense_category_id' => $category->id,
                'name' => 'Netflix',
                'amount' => 14,
                'color' => 'red',
            ])
            ->assertSessionHasErrors('color');
    });
});

it('exposes expense colors on the dashboard', function () {
    $category = ExpenseCategory::factory()->create(['color' => '#3b82f6']);
    Expense::factory()->create(['expense_category_id' => $category->id, 'color' => '#ef4444']);

    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenseCategories.0.color', '#3b82f6')
            ->where('expenseCategories.0.expenses.0.color', '#ef4444'));
});
