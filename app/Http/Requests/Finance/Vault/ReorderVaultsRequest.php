<?php

namespace App\Http\Requests\Finance\Vault;

use Illuminate\Foundation\Http\FormRequest;

class ReorderVaultsRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:vaults,id'],
        ];
    }
}
