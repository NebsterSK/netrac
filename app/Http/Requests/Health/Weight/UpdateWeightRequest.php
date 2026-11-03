<?php

namespace App\Http\Requests\Health\Weight;

use App\Models\Health\Weight;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWeightRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        /** @var Weight $weight */
        $weight = $this->route('weight');

        return [
            'date' => ['required', 'date_format:Y-m-d', Rule::unique('weights', 'date')->ignore($weight->id)],
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
