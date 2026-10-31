<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A valid statement with 1000 in every asset.
 */
function statementPayload(array $overrides = []): array
{
    return array_merge([
        'date' => '2024-05-01',
        'amounts' => Asset::pluck('id')->mapWithKeys(fn (int $assetId): array => [$assetId => 1000])->all(),
        'is_draft' => false,
    ], $overrides);
}

function accountAsset(): Asset
{
    return Asset::where('name', 'Account')->sole();
}

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

it('lists assets alphabetically', function () {
    $this->actingAs(verifiedUser())
        ->get(route('finance.net-worth.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('assets', 6)
            ->where('assets.0.name', 'Account')
            ->where('assets.0.color', '#9ca3af')
            ->where('assets.1.name', 'Finax')
            ->where('assets.5.name', 'Uniqa SDS'));
});

it('lists statements newest first with their asset amounts', function () {
    $account = accountAsset();

    Statement::factory()->create(['date' => '2024-01-01']);
    Statement::factory()->withAmount($account, 12345)->create(['date' => '2024-03-01']);

    $this->actingAs(verifiedUser())
        ->get(route('finance.net-worth.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/NetWorth')
            ->has('statements', 2)
            ->where("statements.0.amounts.{$account->id}", 12345));
});

it('stores a statement with an amount for every asset', function () {
    $account = accountAsset();

    $this->actingAs(verifiedUser())
        ->post(route('finance.net-worth.store'), statementPayload())
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $statement = Statement::sole();

    expect($statement->assets)->toHaveCount(Asset::count())
        ->and($statement->amountIn($account))->toBe(1000);
});

it('requires an amount for every asset on store', function () {
    $assetErrors = Asset::pluck('id')->map(fn (int $assetId): string => "amounts.{$assetId}")->all();

    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), ['amounts' => ['placeholder' => 1]])
        ->assertSessionHasErrors(['date', 'amounts', ...$assetErrors]);
});

it('names the asset in amount errors', function () {
    $account = accountAsset();
    $payload = statementPayload();
    unset($payload['amounts'][$account->id]);

    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), $payload)
        ->assertSessionHasErrors(["amounts.{$account->id}" => 'The Account field is required.']);
});

it('rejects an amount for an unknown asset', function () {
    $payload = statementPayload();
    $payload['amounts'][999999] = 1000;

    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), $payload)
        ->assertSessionHasErrors('amounts');

    expect(Statement::count())->toBe(0);
});

it('rejects negative amounts', function () {
    $account = accountAsset();
    $payload = statementPayload();
    $payload['amounts'][$account->id] = -1;

    $this->actingAs(verifiedUser())
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), $payload)
        ->assertSessionHasErrors("amounts.{$account->id}");
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

it('updates a statement', function () {
    $account = accountAsset();
    $statement = Statement::factory()->withAmount($account, 100)->create(['date' => '2024-07-01']);

    $payload = statementPayload(['date' => '2024-08-01']);
    $payload['amounts'][$account->id] = 777;

    $this->actingAs(verifiedUser())
        ->patch(route('finance.net-worth.update', $statement), $payload)
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $statement->refresh();

    expect($statement->amountIn($account))->toBe(777)
        ->and($statement->assets)->toHaveCount(Asset::count())
        ->and($statement->date->toDateString())->toBe('2024-08-01');
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

it('deletes a statement with its amounts', function () {
    $statement = Statement::factory()->withAmount(accountAsset(), 500)->create();

    $this->actingAs(verifiedUser())
        ->delete(route('finance.net-worth.destroy', $statement))
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($statement);
    $this->assertDatabaseMissing('asset_statement', ['statement_id' => $statement->id]);
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
        $account = accountAsset();
        $draft = Statement::factory()->draft()->create(['date' => '2024-05-01']);

        $payload = statementPayload();
        $payload['amounts'][$account->id] = 999;

        $this->actingAs(verifiedUser())
            ->put(route('finance.net-worth.update', $draft), $payload)
            ->assertSessionHasNoErrors();

        $draft->refresh();

        expect($draft->is_draft)->toBeFalse()
            ->and($draft->amountIn($account))->toBe(999);
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
