<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Expense;
use App\Models\Finance\ExpenseCategory;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = verifiedUser();
    $this->account = Asset::factory()->for($this->owner, 'owner')->tracksMonthlyBalance()->create();
});

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs($this->owner)
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
    $this->actingAs($this->owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periodAverages.overall', null)
            ->where('periodAverages.last6', null));
});

it('averages derived balances per calendar month', function () {
    balanceStatement($this->owner, $this->account, '2023-02-01', 1000);
    balanceStatement($this->owner, $this->account, '2023-03-01', 1100);
    balanceStatement($this->owner, $this->account, '2024-02-01', 2000);
    balanceStatement($this->owner, $this->account, '2024-03-01', 2201);

    $this->actingAs($this->owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('monthlyAverages', 2)
            ->where('monthlyAverages.0.month', 2)
            ->where('monthlyAverages.0.average', 900)
            ->where('monthlyAverages.0.count', 1)
            ->where('monthlyAverages.1.month', 3)
            ->where('monthlyAverages.1.average', 151)
            ->where('monthlyAverages.1.count', 2));
});

it('computes the period averages from derived balances', function () {
    balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
    balanceStatement($this->owner, $this->account, '2024-02-01', 1100);
    balanceStatement($this->owner, $this->account, '2024-03-01', 1300);
    balanceStatement($this->owner, $this->account, '2024-04-01', 1600);

    $this->actingAs($this->owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periodAverages.overall', 200)
            ->where('periodAverages.last6', 200));
});

it('leaves a draft balance out of the averages', function () {
    balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
    balanceStatement($this->owner, $this->account, '2024-02-01', 1100);
    balanceStatement($this->owner, $this->account, '2024-03-01', 9000, ['is_draft' => true]);

    $this->actingAs($this->owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('periodAverages.overall', 100)
            ->has('monthlyAverages', 1));
});

it('returns every expense category with its expenses and total, in saved order', function () {
    $subscriptions = ExpenseCategory::factory()->create(['name' => 'Subscriptions', 'position' => 2]);
    $utilities = ExpenseCategory::factory()->create(['name' => 'Utilities', 'position' => 1]);

    Expense::factory()->for($this->owner, 'owner')->create(['expense_category_id' => $subscriptions->id, 'name' => 'Netflix', 'amount' => 13]);
    Expense::factory()->for($this->owner, 'owner')->create(['expense_category_id' => $subscriptions->id, 'name' => 'YouTube', 'amount' => 20]);
    Expense::factory()->for($this->owner, 'owner')->create(['expense_category_id' => $utilities->id, 'name' => 'Water', 'amount' => 40]);

    $this->actingAs($this->owner)
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

    $this->actingAs($this->owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('expenseCategories', 0));
});

describe('owner filter', function () {
    beforeEach(function () {
        $this->partner = verifiedUser();
        $this->partnerAccount = Asset::factory()->for($this->partner, 'owner')->tracksMonthlyBalance()->create();
    });

    it("averages only the filtered owner's balances", function () {
        balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
        balanceStatement($this->owner, $this->account, '2024-02-01', 1100);
        balanceStatement($this->partner, $this->partnerAccount, '2024-01-01', 100);
        balanceStatement($this->partner, $this->partnerAccount, '2024-02-01', 400);

        $this->owner->update(['finance_owner_id' => $this->partner->id]);

        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('periodAverages.overall', 300)
                ->where('monthlyAverages.0.average', 300));
    });

    it('averages the summed balances under All, leaving out incomplete months', function () {
        balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
        balanceStatement($this->owner, $this->account, '2024-02-01', 1100);
        balanceStatement($this->owner, $this->account, '2024-03-01', 1200);
        balanceStatement($this->partner, $this->partnerAccount, '2024-01-01', 100);
        balanceStatement($this->partner, $this->partnerAccount, '2024-02-01', 400);

        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('periodAverages.overall', 400)
                ->has('monthlyAverages', 1));
    });

    it("breaks down only the filtered owner's expenses", function () {
        $utilities = ExpenseCategory::factory()->create(['name' => 'Utilities']);
        $hobbies = ExpenseCategory::factory()->create(['name' => 'Hobbies']);

        Expense::factory()->for($this->owner, 'owner')->create(['expense_category_id' => $utilities->id, 'name' => 'Water', 'amount' => 40]);
        Expense::factory()->for($this->partner, 'owner')->create(['expense_category_id' => $utilities->id, 'name' => 'Power', 'amount' => 60]);
        Expense::factory()->for($this->partner, 'owner')->create(['expense_category_id' => $hobbies->id, 'name' => 'Yarn', 'amount' => 15]);

        $this->owner->update(['finance_owner_id' => $this->owner->id]);

        $this->actingAs($this->owner)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('expenseCategories', 1)
                ->where('expenseCategories.0.name', 'Utilities')
                ->where('expenseCategories.0.total', 40)
                ->has('expenseCategories.0.expenses', 1)
                ->where('expenseCategories.0.expenses.0.name', 'Water'));
    });
});
