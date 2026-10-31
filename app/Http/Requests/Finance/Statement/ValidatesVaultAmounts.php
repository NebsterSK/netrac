<?php

namespace App\Http\Requests\Finance\Statement;

use App\Models\Finance\Vault;
use Illuminate\Support\Collection;

/**
 * A statement records one amount for every vault, keyed by vault id.
 */
trait ValidatesVaultAmounts
{
    /** @var Collection<int, Vault>|null */
    private ?Collection $vaults = null;

    /**
     * @return Collection<int, Vault>
     */
    private function vaults(): Collection
    {
        return $this->vaults ??= Vault::orderBy('position')->get();
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function amountRules(): array
    {
        $vaultIds = $this->vaults()->pluck('id');

        $rules = [
            'amounts' => $vaultIds->isEmpty()
                ? ['present', 'array', 'max:0']
                : ['required', 'array', 'array:'.$vaultIds->implode(',')],
        ];

        foreach ($vaultIds as $vaultId) {
            $rules["amounts.{$vaultId}"] = ['required', 'integer', 'min:0', 'max:2147483647'];
        }

        return $rules;
    }

    /**
     * Name each amount after its vault in error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->vaults()
            ->mapWithKeys(fn (Vault $vault): array => ["amounts.{$vault->id}" => $vault->name])
            ->all();
    }
}
