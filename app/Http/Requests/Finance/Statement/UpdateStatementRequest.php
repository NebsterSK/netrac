<?php

namespace App\Http\Requests\Finance\Statement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatementRequest extends FormRequest
{
    use ValidatesAssetAmounts;
    use ValidatesDraftPosition;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', Rule::unique('statements', 'date')->ignore($this->route('statement'))],
            ...$this->amountRules(),
            'note' => ['nullable', 'string', 'max:255'],
            'is_draft' => ['required', 'boolean'],
        ];
    }
}
