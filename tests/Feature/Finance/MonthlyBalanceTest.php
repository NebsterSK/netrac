<?php

use App\Models\Finance\Asset;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = verifiedUser(['name' => 'Lukas']);
    $this->account = Asset::factory()->for($this->owner, 'owner')->tracksMonthlyBalance()->create();
});

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('finance.monthly-balance.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs($this->owner)
            ->get(route('finance.monthly-balance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('finance/MonthlyBalance'));
    });
});

it('derives each balance from the account change since the previous statement, newest first', function () {
    balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
    balanceStatement($this->owner, $this->account, '2024-02-01', 1500, ['note' => 'Bonus']);
    balanceStatement($this->owner, $this->account, '2024-03-01', 1200);

    $this->actingAs($this->owner)
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('balances', 2)
            ->where('balances.0.date', '2024-03-01')
            ->where('balances.0.amount', -300)
            ->where('balances.0.note', null)
            ->where('balances.1.date', '2024-02-01')
            ->where('balances.1.amount', 500)
            ->where('balances.1.note', 'Lukas: Bonus'));
});

it('follows whichever asset tracks the monthly balance', function () {
    $this->account->update(['tracks_monthly_balance' => false]);
    $savings = Asset::factory()->for($this->owner, 'owner')->tracksMonthlyBalance()->create();

    balanceStatement($this->owner, $savings, '2024-01-01', 300);
    balanceStatement($this->owner, $savings, '2024-02-01', 350);

    $this->actingAs($this->owner)
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('balances', 1)
            ->where('balances.0.amount', 50));
});

it('yields no balances when no asset tracks the monthly balance', function () {
    $this->account->update(['tracks_monthly_balance' => false]);

    balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
    balanceStatement($this->owner, $this->account, '2024-02-01', 1500);

    $this->actingAs($this->owner)
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page->has('balances', 0));
});

it('yields no balance from a single statement', function () {
    balanceStatement($this->owner, $this->account, '2024-01-01', 1000);

    $this->actingAs($this->owner)
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page->has('balances', 0));
});

it('marks the balance of a draft statement as a draft', function () {
    balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
    balanceStatement($this->owner, $this->account, '2024-02-01', 1400, ['is_draft' => true]);

    $this->actingAs($this->owner)
        ->get(route('finance.monthly-balance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('balances.0.amount', 400)
            ->where('balances.0.is_draft', true));
});

it('no longer accepts manual balances', function () {
    $this->actingAs($this->owner)
        ->post('/finance/monthly-balance', ['date' => '2024-05-01', 'amount' => 1500])
        ->assertMethodNotAllowed();
});

describe('owner filter', function () {
    beforeEach(function () {
        $this->partner = verifiedUser(['name' => 'Lucka']);
        $this->partnerAccount = Asset::factory()->for($this->partner, 'owner')->tracksMonthlyBalance()->create();
    });

    it("shows only the filtered owner's series with the plain note", function () {
        balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
        balanceStatement($this->owner, $this->account, '2024-02-01', 1500, ['note' => 'Bonus']);
        balanceStatement($this->partner, $this->partnerAccount, '2024-01-01', 100);
        balanceStatement($this->partner, $this->partnerAccount, '2024-02-01', 900);

        $this->owner->update(['finance_owner_id' => $this->owner->id]);

        $this->actingAs($this->owner)
            ->get(route('finance.monthly-balance.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('balances', 1)
                ->where('balances.0.amount', 500)
                ->where('balances.0.note', 'Bonus'));
    });

    it('sums every owner per month under All and joins their notes with names', function () {
        balanceStatement($this->owner, $this->account, '2024-01-15', 1000);
        balanceStatement($this->owner, $this->account, '2024-02-15', 1500, ['note' => 'Bonus']);
        balanceStatement($this->partner, $this->partnerAccount, '2024-01-01', 100);
        balanceStatement($this->partner, $this->partnerAccount, '2024-02-01', 300, ['note' => 'Gift']);

        $this->actingAs($this->owner)
            ->get(route('finance.monthly-balance.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('balances', 1)
                ->where('balances.0.date', '2024-02-01')
                ->where('balances.0.amount', 700)
                ->where('balances.0.note', 'Lukas: Bonus · Lucka: Gift')
                ->where('balances.0.is_draft', false));
    });

    it('marks a month as a draft when a required owner has no balance for it', function () {
        balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
        balanceStatement($this->owner, $this->account, '2024-02-01', 1100);
        balanceStatement($this->owner, $this->account, '2024-03-01', 1300);
        balanceStatement($this->partner, $this->partnerAccount, '2024-01-01', 100);
        balanceStatement($this->partner, $this->partnerAccount, '2024-02-01', 200);

        $this->actingAs($this->owner)
            ->get(route('finance.monthly-balance.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('balances', 2)
                ->where('balances.0.date', '2024-03-01')
                ->where('balances.0.amount', 200)
                ->where('balances.0.is_draft', true)
                ->where('balances.1.is_draft', false));
    });

    it("marks a month as a draft when a required owner's balance is a draft", function () {
        balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
        balanceStatement($this->owner, $this->account, '2024-02-01', 1100);
        balanceStatement($this->partner, $this->partnerAccount, '2024-01-01', 100);
        balanceStatement($this->partner, $this->partnerAccount, '2024-02-01', 200, ['is_draft' => true]);

        $this->actingAs($this->owner)
            ->get(route('finance.monthly-balance.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('balances.0.amount', 200)
                ->where('balances.0.is_draft', true));
    });

    it("doesn't mark months before an owner's first balance as drafts", function () {
        balanceStatement($this->owner, $this->account, '2024-01-01', 1000);
        balanceStatement($this->owner, $this->account, '2024-02-01', 1100);
        balanceStatement($this->owner, $this->account, '2024-03-01', 1300);
        balanceStatement($this->owner, $this->account, '2024-04-01', 1600);
        balanceStatement($this->partner, $this->partnerAccount, '2024-02-01', 100);
        balanceStatement($this->partner, $this->partnerAccount, '2024-03-01', 150);

        $this->actingAs($this->owner)
            ->get(route('finance.monthly-balance.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('balances', 3)
                ->where('balances.0.date', '2024-04-01')
                ->where('balances.0.is_draft', true)
                ->where('balances.1.date', '2024-03-01')
                ->where('balances.1.amount', 250)
                ->where('balances.1.is_draft', false)
                ->where('balances.2.date', '2024-02-01')
                ->where('balances.2.amount', 100)
                ->where('balances.2.is_draft', false));
    });
});
