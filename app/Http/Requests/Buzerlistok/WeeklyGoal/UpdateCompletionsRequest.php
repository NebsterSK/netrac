<?php

namespace App\Http\Requests\Buzerlistok\WeeklyGoal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompletionsRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'completions' => ['required', 'integer', 'min:0', 'max:99'],
        ];
    }
}
