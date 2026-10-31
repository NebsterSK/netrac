<?php

namespace App\Http\Requests\Finance\Statement;

use App\Models\Finance\Asset;
use Illuminate\Support\Collection;

/**
 * A statement records one amount for every asset, keyed by asset id.
 */
trait ValidatesAssetAmounts
{
    /** @var Collection<int, Asset>|null */
    private ?Collection $assets = null;

    /**
     * @return Collection<int, Asset>
     */
    private function assets(): Collection
    {
        return $this->assets ??= Asset::orderBy('name')->get();
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function amountRules(): array
    {
        $assetIds = $this->assets()->pluck('id');

        $rules = [
            'amounts' => $assetIds->isEmpty()
                ? ['present', 'array', 'max:0']
                : ['required', 'array', 'array:'.$assetIds->implode(',')],
        ];

        foreach ($assetIds as $assetId) {
            $rules["amounts.{$assetId}"] = ['required', 'integer', 'min:0', 'max:2147483647'];
        }

        return $rules;
    }

    /**
     * Name each amount after its asset in error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->assets()
            ->mapWithKeys(fn (Asset $asset): array => ["amounts.{$asset->id}" => $asset->name])
            ->all();
    }
}
