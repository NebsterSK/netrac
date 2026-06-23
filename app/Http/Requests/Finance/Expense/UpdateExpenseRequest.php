<?php

namespace App\Http\Requests\Finance\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('expenses', 'name')->ignore($this->route('expense'))],
            'amount' => ['required', 'integer', 'min:0'],
        ];
    }
}
