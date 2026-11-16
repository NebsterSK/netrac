<?php

namespace App\Data\Buzerlistok;

use Spatie\LaravelData\Data;

/**
 * Write-side payload built from validated FormRequest data.
 *
 * Validation stays in the FormRequest; this DTO is only the typed carrier
 * between the validated request and the model (the FormRequest->DTO bridge).
 */
class StoreWeekData extends Data
{
    /**
     * @param  array<int, string>  $goals
     * @param  array<int, array{name: string, target: int}>  $weekly_goals
     */
    public function __construct(
        public string $starts_on,
        public array $goals = [],
        public array $weekly_goals = [],
    ) {}
}
