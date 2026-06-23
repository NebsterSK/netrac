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
                ->has('periodAverages'));
    });
});

it('reports null period averages with no balances', function () {
    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periodAverages.overall', null)
            ->where('periodAverages.last6', null));
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

it('aggregates expense totals by category, highest first', function () {
    $rent = ExpenseCategory::factory()->create(['name' => 'Rent']);
    $food = ExpenseCategory::factory()->create(['name' => 'Food']);

    Expense::factory()->create(['expense_category_id' => $rent->id, 'amount' => 800]);
    Expense::factory()->create(['expense_category_id' => $food->id, 'amount' => 100]);
    Expense::factory()->create(['expense_category_id' => $food->id, 'amount' => 50]);

    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('expenseTotal', 950)
            ->has('expenseCategoryTotals', 2)
            ->where('expenseCategoryTotals.0.name', 'Rent')
            ->where('expenseCategoryTotals.0.total', 800)
            ->where('expenseCategoryTotals.1.name', 'Food')
            ->where('expenseCategoryTotals.1.total', 150)
            ->where('expenseCategoryTotals.1.count', 2));
});
