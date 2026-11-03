<?php

use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use App\Models\User;
use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = verifiedUser();

    Asset::factory()->for($this->owner, 'owner')->create(['name' => 'Account', 'color' => '#9ca3af']);
    Asset::factory()->for($this->owner, 'owner')->create(['name' => 'Finax']);
});

/**
 * A valid statement for the owner with 1000 in each of the owner's assets.
 */
function statementPayload(User $owner, array $overrides = []): array
{
    return array_merge([
        'user_id' => $owner->id,
        'date' => '2024-05-01',
        'amounts' => Asset::query()->ownedBy($owner->id)->pluck('id')->mapWithKeys(fn (int $assetId): array => [$assetId => 1000])->all(),
        'is_draft' => false,
    ], $overrides);
}

/**
 * A valid update for the owner's statement; the owner itself can't be sent.
 */
function statementUpdatePayload(User $owner, array $overrides = []): array
{
    return Arr::except(statementPayload($owner, $overrides), 'user_id');
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
        $this->actingAs($this->owner)
            ->get(route('finance.net-worth.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('finance/NetWorth'));
    });
});

it('lists assets alphabetically', function () {
    $this->actingAs($this->owner)
        ->get(route('finance.net-worth.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('assets', 2)
            ->where('assets.0.name', 'Account')
            ->where('assets.0.color', '#9ca3af')
            ->where('assets.0.user_id', $this->owner->id)
            ->where('assets.1.name', 'Finax'));
});

it('lists months newest first with their asset amounts', function () {
    $account = accountAsset();

    Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-01-01']);
    Statement::factory()->for($this->owner, 'owner')->withAmount($account, 12345)->create(['date' => '2024-03-01']);

    $this->actingAs($this->owner)
        ->get(route('finance.net-worth.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/NetWorth')
            ->has('months', 2)
            ->where('months.0.month', '2024-03')
            ->where("months.0.amounts.{$account->id}", 12345)
            ->where("months.0.statements.0.amounts.{$account->id}", 12345));
});

it('stores a statement with an amount for every asset', function () {
    $account = accountAsset();

    $this->actingAs($this->owner)
        ->post(route('finance.net-worth.store'), statementPayload($this->owner))
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $statement = Statement::sole();

    expect($statement->assets)->toHaveCount(Asset::count())
        ->and($statement->amountIn($account))->toBe(1000);
});

it('requires an amount for every asset on store', function () {
    $assetErrors = Asset::pluck('id')->map(fn (int $assetId): string => "amounts.{$assetId}")->all();

    $this->actingAs($this->owner)
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), ['user_id' => $this->owner->id, 'amounts' => ['placeholder' => 1]])
        ->assertSessionHasErrors(['date', 'amounts', ...$assetErrors]);
});

it('names the asset in amount errors', function () {
    $account = accountAsset();
    $payload = statementPayload($this->owner);
    unset($payload['amounts'][$account->id]);

    $this->actingAs($this->owner)
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), $payload)
        ->assertSessionHasErrors(["amounts.{$account->id}" => 'The Account field is required.']);
});

it('rejects an amount for an unknown asset', function () {
    $payload = statementPayload($this->owner);
    $payload['amounts'][999999] = 1000;

    $this->actingAs($this->owner)
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), $payload)
        ->assertSessionHasErrors('amounts');

    expect(Statement::count())->toBe(0);
});

it('rejects negative amounts', function () {
    $account = accountAsset();
    $payload = statementPayload($this->owner);
    $payload['amounts'][$account->id] = -1;

    $this->actingAs($this->owner)
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), $payload)
        ->assertSessionHasErrors("amounts.{$account->id}");
});

it('rejects a second statement in the same month for the same owner', function () {
    Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-06-01']);

    $this->actingAs($this->owner)
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), statementPayload($this->owner, ['date' => '2024-06-15']))
        ->assertSessionHasErrors(['date' => 'This owner already has a statement for this month.']);
});

it('blocks guests from storing', function () {
    $this->post(route('finance.net-worth.store'), statementPayload($this->owner))
        ->assertRedirect(route('login'));

    expect(Statement::count())->toBe(0);
});

it('updates a statement', function () {
    $account = accountAsset();
    $statement = Statement::factory()->for($this->owner, 'owner')->withAmount($account, 100)->create(['date' => '2024-07-01']);

    $payload = statementUpdatePayload($this->owner, ['date' => '2024-08-01']);
    $payload['amounts'][$account->id] = 777;

    $this->actingAs($this->owner)
        ->patch(route('finance.net-worth.update', $statement), $payload)
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $statement->refresh();

    expect($statement->amountIn($account))->toBe(777)
        ->and($statement->assets)->toHaveCount(Asset::count())
        ->and($statement->date->toDateString())->toBe('2024-08-01');
});

it('stores a note with a statement', function () {
    $this->actingAs($this->owner)
        ->post(route('finance.net-worth.store'), statementPayload($this->owner, ['note' => 'Wedding gifts']))
        ->assertRedirect(route('finance.net-worth.index'));

    expect(Statement::sole()->note)->toBe('Wedding gifts');
});

it('rejects a note longer than 255 characters', function () {
    $this->actingAs($this->owner)
        ->from(route('finance.net-worth.index'))
        ->post(route('finance.net-worth.store'), statementPayload($this->owner, ['note' => str_repeat('a', 256)]))
        ->assertSessionHasErrors(['note' => 'The note field must not be greater than 255 characters.']);

    expect(Statement::count())->toBe(0);
});

it('deletes a statement with its amounts', function () {
    $statement = Statement::factory()->for($this->owner, 'owner')->withAmount(accountAsset(), 500)->create();

    $this->actingAs($this->owner)
        ->delete(route('finance.net-worth.destroy', $statement))
        ->assertRedirect(route('finance.net-worth.index'))
        ->assertSessionHas('success');

    $this->assertModelMissing($statement);
    $this->assertDatabaseMissing('asset_statement', ['statement_id' => $statement->id]);
});

describe('drafts', function () {
    it('stores a draft after the latest statement', function () {
        Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-04-01']);

        $this->actingAs($this->owner)
            ->post(route('finance.net-worth.store'), statementPayload($this->owner, ['is_draft' => true]))
            ->assertRedirect(route('finance.net-worth.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('statements', ['date' => '2024-05-01', 'is_draft' => true]);
    });

    it('exposes the draft flag to the page', function () {
        Statement::factory()->for($this->owner, 'owner')->draft()->create(['date' => '2024-05-01']);

        $this->actingAs($this->owner)
            ->get(route('finance.net-worth.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('months.0.is_draft', true)
                ->where('months.0.statements.0.is_draft', true));
    });

    it('rejects a draft dated before the latest statement', function () {
        Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-06-01']);

        $this->actingAs($this->owner)
            ->from(route('finance.net-worth.index'))
            ->post(route('finance.net-worth.store'), statementPayload($this->owner, ['is_draft' => true]))
            ->assertSessionHasErrors('is_draft');
    });

    it('rejects a second draft', function () {
        Statement::factory()->for($this->owner, 'owner')->draft()->create(['date' => '2024-04-01']);

        $this->actingAs($this->owner)
            ->from(route('finance.net-worth.index'))
            ->post(route('finance.net-worth.store'), statementPayload($this->owner, ['is_draft' => true]))
            ->assertSessionHasErrors('is_draft');
    });

    it('rejects a statement dated after the draft', function () {
        Statement::factory()->for($this->owner, 'owner')->draft()->create(['date' => '2024-04-01']);

        $this->actingAs($this->owner)
            ->from(route('finance.net-worth.index'))
            ->post(route('finance.net-worth.store'), statementPayload($this->owner))
            ->assertSessionHasErrors('date');
    });

    it('allows a statement dated before the draft', function () {
        Statement::factory()->for($this->owner, 'owner')->draft()->create(['date' => '2024-06-01']);

        $this->actingAs($this->owner)
            ->post(route('finance.net-worth.store'), statementPayload($this->owner))
            ->assertSessionHasNoErrors();

        expect(Statement::count())->toBe(2);
    });

    it('finalizes a draft', function () {
        $account = accountAsset();
        $draft = Statement::factory()->for($this->owner, 'owner')->draft()->create(['date' => '2024-05-01']);

        $payload = statementUpdatePayload($this->owner);
        $payload['amounts'][$account->id] = 999;

        $this->actingAs($this->owner)
            ->put(route('finance.net-worth.update', $draft), $payload)
            ->assertSessionHasNoErrors();

        $draft->refresh();

        expect($draft->is_draft)->toBeFalse()
            ->and($draft->amountIn($account))->toBe(999);
    });

    it('turns the latest statement into a draft', function () {
        Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-04-01']);
        $latest = Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-05-01']);

        $this->actingAs($this->owner)
            ->put(route('finance.net-worth.update', $latest), statementUpdatePayload($this->owner, ['is_draft' => true]))
            ->assertSessionHasNoErrors();

        expect($latest->refresh()->is_draft)->toBeTrue();
    });

    it('rejects turning an older statement into a draft', function () {
        $older = Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-05-01']);
        Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-06-01']);

        $this->actingAs($this->owner)
            ->from(route('finance.net-worth.index'))
            ->put(route('finance.net-worth.update', $older), statementUpdatePayload($this->owner, ['is_draft' => true]))
            ->assertSessionHasErrors('is_draft');
    });
});

describe('owners', function () {
    it('requires an owner on store', function () {
        $this->actingAs($this->owner)
            ->from(route('finance.net-worth.index'))
            ->post(route('finance.net-worth.store'), Arr::except(statementPayload($this->owner), 'user_id'))
            ->assertSessionHasErrors('user_id');

        expect(Statement::count())->toBe(0);
    });

    it('stores the statement for the picked owner', function () {
        $this->actingAs(verifiedUser())
            ->post(route('finance.net-worth.store'), statementPayload($this->owner))
            ->assertSessionHasNoErrors();

        expect(Statement::sole()->user_id)->toBe($this->owner->id);
    });

    it('accepts the same month for another owner', function () {
        $other = verifiedUser();
        Asset::factory()->for($other, 'owner')->create();
        Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-05-01']);

        $this->actingAs($this->owner)
            ->post(route('finance.net-worth.store'), statementPayload($other))
            ->assertSessionHasNoErrors();

        expect(Statement::where('user_id', $other->id)->count())->toBe(1);
    });

    it("rejects an amount for another owner's asset", function () {
        $otherAsset = Asset::factory()->for(verifiedUser(), 'owner')->create();
        $payload = statementPayload($this->owner);
        $payload['amounts'][$otherAsset->id] = 1000;

        $this->actingAs($this->owner)
            ->from(route('finance.net-worth.index'))
            ->post(route('finance.net-worth.store'), $payload)
            ->assertSessionHasErrors('amounts');

        expect(Statement::count())->toBe(0);
    });

    it('rejects changing the owner on update', function () {
        $statement = Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-05-01']);
        $other = verifiedUser();

        $this->actingAs($this->owner)
            ->from(route('finance.net-worth.index'))
            ->put(route('finance.net-worth.update', $statement), statementPayload($this->owner, ['user_id' => $other->id]))
            ->assertSessionHasErrors('user_id');

        expect($statement->refresh()->user_id)->toBe($this->owner->id);
    });

    it("rejects moving a statement into a month its owner already has", function () {
        Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-05-01']);
        $statement = Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-04-01']);

        $this->actingAs($this->owner)
            ->from(route('finance.net-worth.index'))
            ->put(route('finance.net-worth.update', $statement), statementUpdatePayload($this->owner, ['date' => '2024-05-20']))
            ->assertSessionHasErrors('date');
    });

    it('keeps drafts per owner', function () {
        $other = verifiedUser();
        Asset::factory()->for($other, 'owner')->create();
        Statement::factory()->for($other, 'owner')->draft()->create(['date' => '2024-04-01']);

        $this->actingAs($this->owner)
            ->post(route('finance.net-worth.store'), statementPayload($this->owner, ['is_draft' => true]))
            ->assertSessionHasNoErrors();

        expect(Statement::where('is_draft', true)->count())->toBe(2);
    });

    it('lists the months each owner already has', function () {
        $other = verifiedUser();
        Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-05-01']);
        Statement::factory()->for($this->owner, 'owner')->create(['date' => '2024-04-01']);
        Statement::factory()->for($other, 'owner')->create(['date' => '2024-05-01']);

        $this->actingAs(verifiedUser(['finance_owner_id' => $this->owner->id]))
            ->get(route('finance.net-worth.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where("existingMonths.{$this->owner->id}", ['2024-04', '2024-05'])
                ->where("existingMonths.{$other->id}", ['2024-05']));
    });
});

describe('combined months', function () {
    beforeEach(function () {
        $this->other = verifiedUser();
        $this->otherAsset = Asset::factory()->for($this->other, 'owner')->create();
        $account = accountAsset();

        Statement::factory()->for($this->owner, 'owner')->withAmount($account, 1000)->create(['date' => '2024-03-01']);
        Statement::factory()->for($this->owner, 'owner')->withAmount($account, 1500)->create(['date' => '2024-04-01']);
        Statement::factory()->for($this->other, 'owner')->withAmount($this->otherAsset, 500)->create(['date' => '2024-04-01']);
        Statement::factory()->for($this->other, 'owner')->withAmount($this->otherAsset, 700)->create(['date' => '2024-05-01']);
    });

    it('groups every owner\'s statements into one month under All', function () {
        $account = accountAsset();

        $this->actingAs($this->owner)
            ->get(route('finance.net-worth.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('assets', 3)
                ->has('months', 3)
                ->where('months.1.month', '2024-04')
                ->has('months.1.statements', 2)
                ->where("months.1.amounts.{$account->id}", 1500)
                ->where("months.1.amounts.{$this->otherAsset->id}", 500)
                ->where('months.1.total', 2000)
                ->where('months.2.gain', null)
                ->where('months.2.gain_percent', null));
    });

    it('leaves a joining owner\'s opening amount out of the gain', function () {
        $this->actingAs($this->owner)
            ->get(route('finance.net-worth.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('months.1.month', '2024-04')
                ->where('months.1.gain', 500)
                ->where('months.1.gain_percent', 50));
    });

    it('counts only owners present in both months after an owner skips a month', function () {
        Statement::factory()->for($this->owner, 'owner')->withAmount(accountAsset(), 3000)->create(['date' => '2024-06-01']);
        Statement::factory()->for($this->other, 'owner')->withAmount($this->otherAsset, 800)->create(['date' => '2024-06-01']);

        $this->actingAs($this->owner)
            ->get(route('finance.net-worth.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('months.0.month', '2024-06')
                ->where('months.0.total', 3800)
                ->where('months.0.gain', 100)
                ->where('months.0.gain_percent', fn (float $percent): bool => abs($percent - 100 / 7) < 0.0001)
                ->where('months.1.month', '2024-05')
                ->where('months.1.gain', 200)
                ->where('months.1.gain_percent', 40));
    });

    it('does not require an owner before their first statement', function () {
        $this->actingAs($this->owner)
            ->get(route('finance.net-worth.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('months.2.month', '2024-03')
                ->where('months.2.is_complete', true)
                ->where('months.1.is_complete', true));
    });

    it('flags a month missing a required owner as incomplete', function () {
        $this->actingAs($this->owner)
            ->get(route('finance.net-worth.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('months.0.month', '2024-05')
                ->has('months.0.statements', 1)
                ->where('months.0.is_complete', false));
    });

    it('lists only the filtered owner\'s statements and assets', function () {
        $this->actingAs(verifiedUser(['finance_owner_id' => $this->other->id]))
            ->get(route('finance.net-worth.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('assets', 1)
                ->where('assets.0.id', $this->otherAsset->id)
                ->has('months', 2)
                ->where('months.0.month', '2024-05')
                ->where('months.0.is_complete', true)
                ->where('months.0.gain', 200)
                ->has('months.0.statements', 1)
                ->where('months.0.statements.0.user_id', $this->other->id));
    });
});
