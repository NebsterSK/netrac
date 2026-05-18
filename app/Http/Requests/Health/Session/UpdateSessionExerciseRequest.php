<?php

namespace App\Http\Requests\Health\Session;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionExerciseRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'completed' => ['required', 'boolean'],
        ];
    }
}
