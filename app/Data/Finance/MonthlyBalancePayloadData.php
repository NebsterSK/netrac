<?php

namespace App\Data\Finance;

use Spatie\LaravelData\Data;

/**
 * Write-side payload built from validated FormRequest data.
 *
 * Validation stays in the FormRequest; this DTO is only the typed carrier
 * between the validated request and the model (the FormRequest->DTO bridge).
 */
class MonthlyBalancePayloadData extends Data
{
    public function __construct(
        public string $date,
        public int $amount,
        public ?string $comment,
    ) {}
}
