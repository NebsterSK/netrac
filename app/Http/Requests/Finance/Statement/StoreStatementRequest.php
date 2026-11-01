<?php

namespace App\Http\Requests\Finance\Statement;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatementRequest extends FormRequest
{
    use ValidatesAssetAmounts;
    use ValidatesDraftPosition;
    use ValidatesUniqueMonth;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['bail', 'required', 'date', $this->uniqueMonthRule()],
            ...$this->amountRules(),
            'note' => ['nullable', 'string', 'max:255'],
            'is_draft' => ['required', 'boolean'],
        ];
    }

    /**
     * The owner picked in the form; null until a usable id is submitted.
     */
    protected function statementOwnerId(): ?int
    {
        $ownerId = filter_var($this->input('user_id'), FILTER_VALIDATE_INT);

        return $ownerId === false ? null : $ownerId;
    }
}
