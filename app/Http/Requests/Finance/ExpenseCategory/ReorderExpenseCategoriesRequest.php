<?php

namespace App\Http\Requests\Finance\ExpenseCategory;

use Illuminate\Foundation\Http\FormRequest;

class ReorderExpenseCategoriesRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:expense_categories,id'],
        ];
    }
}
