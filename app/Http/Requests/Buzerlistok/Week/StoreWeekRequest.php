<?php

namespace App\Http\Requests\Buzerlistok\Week;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreWeekRequest extends FormRequest
{
    /**
     * Snap the requested date to the Monday of its week so there is exactly one
     * buzerlístek per calendar week.
     */
    protected function prepareForValidation(): void
    {
        $startsOn = $this->input('starts_on');

        if (is_string($startsOn) && $startsOn !== '') {
            try {
                $this->merge([
                    'starts_on' => Carbon::parse($startsOn)->startOfWeek()->toDateString(),
                ]);
            } catch (\Throwable) {
                // Leave the raw value in place; the `date` rule reports the error.
            }
        }
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'starts_on' => ['required', 'date', Rule::unique('buzerlistok_weeks', 'starts_on')],
            'goals' => ['required', 'array', 'min:1'],
            'goals.*' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_on.unique' => 'A buzerlístek already exists for that week.',
        ];
    }
}
