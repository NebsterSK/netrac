<?php

namespace App\Http\Requests\Health\Exercise;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExerciseRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'exercise_category_id' => ['required', 'integer', 'exists:exercise_categories,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('exercises', 'name')->ignore($this->route('exercise'))],
        ];
    }
}
