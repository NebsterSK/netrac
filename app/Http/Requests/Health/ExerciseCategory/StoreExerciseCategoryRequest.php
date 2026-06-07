<?php

namespace App\Http\Requests\Health\ExerciseCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreExerciseCategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:exercise_categories,name'],
        ];
    }
}
