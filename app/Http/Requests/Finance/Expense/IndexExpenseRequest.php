<?php

namespace App\Http\Requests\Finance\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexExpenseRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'array'],
            'filter.name' => ['nullable', 'string', 'max:255'],
            'filter.expense_category_id' => ['nullable', 'string'],
            'sort' => ['nullable', Rule::in([
                'name', '-name',
                'amount', '-amount',
                'created_at', '-created_at',
                'updated_at', '-updated_at',
                'category', '-category',
            ])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
