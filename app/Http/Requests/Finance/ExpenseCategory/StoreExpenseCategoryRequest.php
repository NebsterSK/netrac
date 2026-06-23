<?php

namespace App\Http\Requests\Finance\ExpenseCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseCategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
        ];
    }
}
