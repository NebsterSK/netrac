<?php

namespace App\Http\Requests\Health\Exercise;

use Illuminate\Foundation\Http\FormRequest;

class StoreExerciseRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'exercise_category_id' => ['required', 'integer', 'exists:exercise_categories,id'],
            'name' => ['required', 'string', 'max:255', 'unique:exercises,name'],
        ];
    }
}
