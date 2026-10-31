<?php

namespace App\Http\Requests\Finance\Vault;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVaultRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('vaults', 'name')->ignore($this->route('vault'))],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tracks_monthly_balance' => ['required', 'boolean'],
        ];
    }
}
