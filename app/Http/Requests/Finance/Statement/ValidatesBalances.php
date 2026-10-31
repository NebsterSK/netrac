<?php

namespace App\Http\Requests\Finance\Statement;

use App\Models\Finance\Asset;

/**
 * Requires an amount for every asset, keyed by asset id, and rejects unknown asset ids.
 */
trait ValidatesBalances
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function balanceRules(): array
    {
        $assetIds = Asset::pluck('id');

        $rules = [
            'balances' => ['present', 'array', 'array:'.$assetIds->implode(',')],
        ];

        foreach ($assetIds as $assetId) {
            $rules["balances.{$assetId}"] = ['required', 'integer', 'min:0', 'max:2147483647'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return Asset::pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int $assetId): array => ["balances.{$assetId}" => $name])
            ->all();
    }
}
