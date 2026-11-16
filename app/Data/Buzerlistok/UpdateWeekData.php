<?php

namespace App\Data\Buzerlistok;

use Spatie\LaravelData\Data;

/**
 * Write-side payload built from validated FormRequest data.
 *
 * Goals (daily and weekly) with an `id` are kept (renamed / reordered), goals
 * without one are created, and existing goals missing from the list are deleted.
 */
class UpdateWeekData extends Data
{
    /**
     * @param  array<int, array{id: int|null, name: string}>  $goals
     * @param  array<int, array{id: int|null, name: string, target: int}>  $weekly_goals
     */
    public function __construct(
        public string $starts_on,
        public array $goals = [],
        public array $weekly_goals = [],
    ) {}
}
