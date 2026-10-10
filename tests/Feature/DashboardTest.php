<?php

use App\Models\Finance\Expense;
use App\Models\Finance\ExpenseCategory;
use App\Models\Finance\MonthlyBalance;
use Inertia\Testing\AssertableInertia as Assert;

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('monthlyAverages')
                ->has('periodAverages')
                ->has('expenseCategories'));
    });
});

it('reports null period averages with no balances', function () {
    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periodAverages.overall', null)
            ->where('periodAverages.last6', null));
});

it('averages balances per calendar month', function () {
    MonthlyBalance::factory()->create(['date' => '2023-03-01', 'amount' => 100]);
    MonthlyBalance::factory()->create(['date' => '2024-03-01', 'amount' => 201]);
    MonthlyBalance::factory()->create(['date' => '2024-07-01', 'amount' => -50]);

    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('monthlyAverages', 2)
            ->where('monthlyAverages.0.month', 3)
            ->where('monthlyAverages.0.average', 151)
            ->where('monthlyAverages.0.count', 2)
            ->where('monthlyAverages.1.month', 7)
            ->where('monthlyAverages.1.average', -50));
});

it('computes the overall average from balances', function () {
    MonthlyBalance::factory()->create(['date' => '2024-01-01', 'amount' => 100]);
    MonthlyBalance::factory()->create(['date' => '2024-02-01', 'amount' => 200]);
    MonthlyBalance::factory()->create(['date' => '2024-03-01', 'amount' => 300]);

    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periodAverages.overall', 200)
            ->where('periodAverages.last6', 200));
});

it('returns every expense category with its expenses and total, in saved order', function () {
    $subscriptions = ExpenseCategory::factory()->create(['name' => 'Subscriptions', 'position' => 2]);
    $utilities = ExpenseCategory::factory()->create(['name' => 'Utilities', 'position' => 1]);

    Expense::factory()->create(['expense_category_id' => $subscriptions->id, 'name' => 'Netflix', 'amount' => 13]);
    Expense::factory()->create(['expense_category_id' => $subscriptions->id, 'name' => 'YouTube', 'amount' => 20]);
    Expense::factory()->create(['expense_category_id' => $utilities->id, 'name' => 'Water', 'amount' => 40]);

    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('expenseCategories', 2)
            ->where('expenseCategories.0.name', 'Utilities')
            ->where('expenseCategories.0.total', 40)
            ->where('expenseCategories.1.name', 'Subscriptions')
            ->where('expenseCategories.1.total', 33)
            ->has('expenseCategories.1.expenses', 2)
            ->where('expenseCategories.1.expenses.0.name', 'YouTube')
            ->where('expenseCategories.1.expenses.0.amount', 20)
            ->where('expenseCategories.1.expenses.1.name', 'Netflix'));
});

it('omits expense categories without expenses', function () {
    ExpenseCategory::factory()->create(['name' => 'Empty']);

    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('expenseCategories', 0));
});
