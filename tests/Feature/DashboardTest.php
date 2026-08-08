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
                ->has('subscriptions'));
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

it('returns subscriptions category expenses, highest amount first', function () {
    $subscriptions = ExpenseCategory::factory()->create(['name' => 'Subscriptions']);
    $utilities = ExpenseCategory::factory()->create(['name' => 'Utilities']);

    Expense::factory()->create(['expense_category_id' => $subscriptions->id, 'name' => 'Netflix', 'amount' => 13]);
    Expense::factory()->create(['expense_category_id' => $subscriptions->id, 'name' => 'YouTube', 'amount' => 20]);
    Expense::factory()->create(['expense_category_id' => $utilities->id, 'name' => 'Water', 'amount' => 40]);

    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('subscriptions', 2)
            ->where('subscriptions.0.name', 'YouTube')
            ->where('subscriptions.0.amount', 20)
            ->where('subscriptions.1.name', 'Netflix'));
});

it('returns no subscriptions when the category is absent', function () {
    $food = ExpenseCategory::factory()->create(['name' => 'Food']);
    Expense::factory()->create(['expense_category_id' => $food->id]);

    $this->actingAs(verifiedUser())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('subscriptions', 0));
});
