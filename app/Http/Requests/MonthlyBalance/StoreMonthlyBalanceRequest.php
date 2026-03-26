<?php

namespace App\Http\Requests\MonthlyBalance;

use Illuminate\Foundation\Http\FormRequest;

class StoreMonthlyBalanceRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'unique:monthly_balances,date'],
            'amount' => ['required', 'integer'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}
