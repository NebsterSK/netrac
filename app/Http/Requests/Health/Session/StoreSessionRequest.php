<?php

namespace App\Http\Requests\Health\Session;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'exercise_ids' => ['required', 'array', 'min:1'],
            'exercise_ids.*' => ['required', 'integer', 'distinct', 'exists:exercises,id'],
        ];
    }
}
