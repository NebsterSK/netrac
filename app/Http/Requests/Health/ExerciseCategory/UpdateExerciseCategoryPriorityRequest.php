<?php

namespace App\Http\Requests\Health\ExerciseCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExerciseCategoryPriorityRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'priority' => ['required', 'integer', 'min:1'],
        ];
    }
}
