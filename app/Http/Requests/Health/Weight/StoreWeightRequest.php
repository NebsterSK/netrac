<?php

namespace App\Http\Requests\Health\Weight;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeightRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'unique:weights,date'],
            'weight' => ['required', 'numeric', 'min:1', 'max:999.9', 'decimal:0,1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.unique' => 'There is already a weight entry for this date.',
        ];
    }
}
