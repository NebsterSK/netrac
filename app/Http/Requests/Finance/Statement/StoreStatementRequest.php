<?php

namespace App\Http\Requests\Finance\Statement;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatementRequest extends FormRequest
{
    use ValidatesDraftPosition;
    use ValidatesVaultAmounts;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'unique:statements,date'],
            ...$this->amountRules(),
            'note' => ['nullable', 'string', 'max:255'],
            'is_draft' => ['required', 'boolean'],
        ];
    }
}
