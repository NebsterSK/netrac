<?php

namespace App\Http\Requests\Buzerlistok\Week;

use App\Models\Buzerlistok\Week;
use Illuminate\Validation\Rule;

class UpdateWeekRequest extends StoreWeekRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        /** @var Week $week */
        $week = $this->route('week');

        return [
            'starts_on' => ['required', 'date', Rule::unique('buzerlistok_weeks', 'starts_on')->ignore($week->id)],
            'goals' => ['required', 'array', 'min:1'],
            'goals.*.id' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('buzerlistok_goals', 'id')->where('week_id', $week->id),
            ],
            'goals.*.name' => ['required', 'string', 'max:255'],
        ];
    }
}
