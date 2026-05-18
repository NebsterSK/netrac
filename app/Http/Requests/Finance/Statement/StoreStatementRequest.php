<?php

namespace App\Http\Requests\Finance\Statement;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatementRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'unique:statements,date'],
            'account' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'legacy_upgrade' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'uniqa_sds' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'uniqa_dds' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'finax' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'trading212' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }
}
