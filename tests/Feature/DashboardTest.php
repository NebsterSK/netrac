<?php

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
