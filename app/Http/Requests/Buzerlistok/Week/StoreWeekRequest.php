<?php

namespace App\Http\Requests\Buzerlistok\Week;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreWeekRequest extends FormRequest
{
    /**
     * Snap the requested date to the Monday of its week so there is exactly one
     * buzerlistok per calendar week.
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
     * A week needs at least one goal, daily or weekly.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'starts_on' => ['required', 'date', Rule::unique('buzerlistok_weeks', 'starts_on')],
            'goals' => ['array', 'required_without:weekly_goals'],
            'goals.*' => ['required', 'string', 'max:255'],
            ...$this->weeklyGoalRules(),
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function weeklyGoalRules(): array
    {
        return [
            'weekly_goals' => ['array'],
            'weekly_goals.*.name' => ['required', 'string', 'max:255'],
            'weekly_goals.*.target' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_on.unique' => 'A buzerlistok already exists for that week.',
            'goals.required_without' => 'Add at least one daily or weekly goal.',
        ];
    }
}
