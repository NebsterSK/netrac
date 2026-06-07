<?php

namespace App\Http\Requests\Health\ExerciseCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExerciseCategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('exercise_categories', 'name')->ignore($this->route('exerciseCategory'))],
        ];
    }
}
