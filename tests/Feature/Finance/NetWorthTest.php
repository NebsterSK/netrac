<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A valid payload with an amount for every existing asset (1000, 2000, … in id order).
 */
function statementPayload(array $overrides = []): array
{
    $balances = Asset::orderBy('id')->pluck('id')
        ->mapWithKeys(fn (int $assetId, int $index): array => [$assetId => ($index + 1) * 1000])
        ->all();

    return array_merge([
        'date' => '2024-05-01',
        'balances' => $balances,
        'is_draft' => false,
    ], $overrides);
}

beforeEach(function () {
    $this->cash = Asset::factory()->create(['name' => 'Account', 'position' => 1]);
    $this->broker = Asset::factory()->create(['name' => 'Broker', 'position' => 2]);
});

describe('access control', function () {
    it('redirects guests to login', function () {
        $this->get(route('finance.net-worth.index'))
            ->assertRedirect(route('login'));
    });

    it('allows authenticated users', function () {
        $this->actingAs(verifiedUser())
            ->get(route('finance.net-worth.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('finance/NetWorth'));
    });
});

it('lists statements newest first with their balances keyed by asset', function () {
    Statement::factory()->create(['date' => '2024-01-01']);
    Statement::factory()->withBalance($this->cash, 12345)->create(['date' => '2024-03-01']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.net-worth.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/NetWorth')
            ->has('statements', 2)
            ->where("statements.0.balances.{$this->cash->id}", 12345));
});

it('lists assets in position order', function () {
    $this->cash->update(['position' => 3]);

    $this->actingAs(verifiedUser())
        ->get(route('finance.net-worth.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('assets', 2)
            ->where('assets.0.name', 'Broker')
            ->where('assets.1.name', 'Account'));
});

it('stores a statement with a balance per asset', function () {
    $this->actingAs(verifiedUser())
        ->post(route('finance.net-worth.store'), statementPayload())
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $statement = Statement::sole();

    expect($statement->balanceOf($this->cash->id))->toBe(1000)
        ->and($statement->balanceOf($this->broker->id))->toBe(2000);
});

it('requires a balance for every asset on store', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), ['balances' => [$this->cash->id => 100]])
        ->assertSessionHasErrors(['date', "balances.{$this->broker->id}" => 'The Broker field is required.']);
});

it('rejects a balance for an unknown asset', function () {
    $payload = statementPayload();
    $payload['balances'][999999] = 100;

    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), $payload)
        ->assertSessionHasErrors('balances');

    expect(Statement::count())->toBe(0);
});

it('rejects negative balances', function () {
    $payload = statementPayload();
    $payload['balances'][$this->cash->id] = -1;

    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), $payload)
        ->assertSessionHasErrors("balances.{$this->cash->id}");
});

it('rejects a duplicate date on store', function () {
    Statement::factory()->create(['date' => '2024-06-01']);

    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), statementPayload(['date' => '2024-06-01']))
        ->assertSessionHasErrors('date');
});

it('blocks guests from storing', function () {
    $this->post(route('finance.net-worth.store'), statementPayload())
        ->assertRedirect(route('login'));

    expect(Statement::count())->toBe(0);
});

it('updates a statement and fills in balances of assets added later', function () {
    $statement = Statement::factory()->withBalance($this->cash, 100)->create(['date' => '2024-07-01']);

    $this->actingAs(verifiedUser())
        ->patch(route('finance.net-worth.update', $statement), statementPayload([
            'date' => '2024-08-01',
            'balances' => [$this->cash->id => 777, $this->broker->id => 50],
        ]))
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $statement->refresh();

    expect($statement->date->toDateString())->toBe('2024-08-01')
        ->and($statement->balances)->toHaveCount(2)
        ->and($statement->balanceOf($this->cash->id))->toBe(777)
        ->and($statement->balanceOf($this->broker->id))->toBe(50);
});

it('stores a note with a statement', function () {
    $this->actingAs(verifiedUser())
        ->post(route('finance.net-worth.store'), statementPayload(['note' => 'Wedding gifts']))
        ->assertRedirect(route('finance.net-worth.index'));

    expect(Statement::sole()->note)->toBe('Wedding gifts');
});

it('rejects a note longer than 255 characters', function () {
    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), statementPayload(['note' => str_repeat('a', 256)]))
        ->assertSessionHasErrors(['note' => 'The note field must not be greater than 255 characters.']);

    expect(Statement::count())->toBe(0);
});

it('deletes a statement with its balances', function () {
    $statement = Statement::factory()->withBalance($this->cash, 100)->create();

    $this->actingAs(verifiedUser())
        ->delete(route('finance.net-worth.destroy', $statement))
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($statement);
    $this->assertDatabaseCount('statement_balances', 0);
});

describe('drafts', function () {
    it('stores a draft after the latest statement', function () {
        Statement::factory()->create(['date' => '2024-04-01']);

        $this->actingAs(verifiedUser())
            ->post(route('finance.net-worth.store'), statementPayload(['is_draft' => true]))
            ->assertRedirect(route('finance.net-worth.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('statements', ['date' => '2024-05-01', 'is_draft' => true]);
    });

    it('exposes the draft flag to the page', function () {
        Statement::factory()->draft()->create(['date' => '2024-05-01']);

        $this->actingAs(verifiedUser())
            ->get(route('finance.net-worth.index'))
            ->assertInertia(fn (Assert $page) => $page->where('statements.0.is_draft', true));
    });

    it('rejects a draft dated before the latest statement', function () {
        Statement::factory()->create(['date' => '2024-06-01']);

        $this->actingAs(verifiedUser())
            ->from(route('finance.net-worth.index'))
            ->post(route('finance.net-worth.store'), statementPayload(['is_draft' => true]))
            ->assertSessionHasErrors('is_draft');
    });

    it('rejects a second draft', function () {
        Statement::factory()->draft()->create(['date' => '2024-04-01']);

        $this->actingAs(verifiedUser())
            ->from(route('finance.net-worth.index'))
            ->post(route('finance.net-worth.store'), statementPayload(['is_draft' => true]))
            ->assertSessionHasErrors('is_draft');
    });

    it('rejects a statement dated after the draft', function () {
        Statement::factory()->draft()->create(['date' => '2024-04-01']);

        $this->actingAs(verifiedUser())
            ->from(route('finance.net-worth.index'))
            ->post(route('finance.net-worth.store'), statementPayload())
            ->assertSessionHasErrors('date');
    });

    it('allows a statement dated before the draft', function () {
        Statement::factory()->draft()->create(['date' => '2024-06-01']);

        $this->actingAs(verifiedUser())
            ->post(route('finance.net-worth.store'), statementPayload())
            ->assertSessionHasNoErrors();

        expect(Statement::count())->toBe(2);
    });

    it('finalizes a draft', function () {
        $draft = Statement::factory()->draft()->create(['date' => '2024-05-01']);

        $this->actingAs(verifiedUser())
            ->put(route('finance.net-worth.update', $draft), statementPayload())
            ->assertSessionHasNoErrors();

        expect($draft->refresh()->is_draft)->toBeFalse()
            ->and($draft->balanceOf($this->cash->id))->toBe(1000);
    });

    it('turns the latest statement into a draft', function () {
        Statement::factory()->create(['date' => '2024-04-01']);
        $latest = Statement::factory()->create(['date' => '2024-05-01']);

        $this->actingAs(verifiedUser())
            ->put(route('finance.net-worth.update', $latest), statementPayload(['is_draft' => true]))
            ->assertSessionHasNoErrors();

        expect($latest->refresh()->is_draft)->toBeTrue();
    });

    it('rejects turning an older statement into a draft', function () {
        $older = Statement::factory()->create(['date' => '2024-05-01']);
        Statement::factory()->create(['date' => '2024-06-01']);

        $this->actingAs(verifiedUser())
            ->from(route('finance.net-worth.index'))
            ->put(route('finance.net-worth.update', $older), statementPayload(['is_draft' => true]))
            ->assertSessionHasErrors('is_draft');
    });
});
