<?php

namespace App\Http\Requests\Finance\Vault;

use Illuminate\Foundation\Http\FormRequest;

class StoreVaultRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:vaults,name'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tracks_monthly_balance' => ['required', 'boolean'],
        ];
    }
}
