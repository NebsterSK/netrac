<?php

namespace App\Http\Requests\Finance\OwnerFilter;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnerFilterRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
