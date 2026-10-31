<?php

namespace App\Http\Requests\Finance\Asset;

use Illuminate\Foundation\Http\FormRequest;

class ReorderAssetsRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:assets,id'],
        ];
    }
}
