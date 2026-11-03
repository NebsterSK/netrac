<?php

namespace App\Http\Requests\Finance\Asset;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $asset = $this->route('asset');

                    if ((int) $value !== $asset->user_id && $asset->statements()->exists()) {
                        $fail('Asset already has recorded amounts; create a new asset for the other owner.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255', Rule::unique('assets', 'name')->where('user_id', $this->input('user_id'))->ignore($this->route('asset'))],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tracks_monthly_balance' => ['required', 'boolean'],
        ];
    }
}
