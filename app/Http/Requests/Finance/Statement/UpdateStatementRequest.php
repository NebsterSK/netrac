<?php

namespace App\Http\Requests\Finance\Statement;

use App\Models\Finance\Statement;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatementRequest extends FormRequest
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
            'user_id' => ['prohibited'],
            'date' => ['bail', 'required', 'date', $this->uniqueMonthRule()],
            ...$this->amountRules(),
            'note' => ['nullable', 'string', 'max:255'],
            'is_draft' => ['required', 'boolean'],
        ];
    }

    /**
     * A statement's owner is fixed once it is created.
     */
    protected function statementOwnerId(): ?int
    {
        /** @var Statement $statement */
        $statement = $this->route('statement');

        return $statement->user_id;
    }
}
