<?php

namespace App\Http\Requests\Finance\ExpenseCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($this->route('expenseCategory'))],
        ];
    }
}
