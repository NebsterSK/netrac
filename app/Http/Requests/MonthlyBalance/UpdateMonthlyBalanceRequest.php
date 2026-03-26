<?php

namespace App\Http\Requests\MonthlyBalance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMonthlyBalanceRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', Rule::unique('monthly_balances', 'date')->ignore($this->route('monthly_balance'))],
            'amount' => ['required', 'integer'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}
