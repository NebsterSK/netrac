<?php

namespace App\Http\Requests\Buzerlistok\Mark;

use App\Enums\Buzerlistok\MarkStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarkRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'marked_on' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(MarkStatus::class)],
        ];
    }
}
