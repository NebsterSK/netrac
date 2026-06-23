<?php

namespace App\Http\Requests\Finance\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'name' => ['required', 'string', 'max:255', 'unique:expenses,name'],
            'amount' => ['required', 'integer', 'min:0'],
        ];
    }
}
