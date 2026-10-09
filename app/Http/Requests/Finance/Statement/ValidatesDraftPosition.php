<?php

namespace App\Http\Requests\Finance\Statement;

use App\Models\Finance\Statement;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Keeps a draft as the single latest statement: at most one draft, dated after every real statement.
 */
trait ValidatesDraftPosition
{
    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $date = Carbon::parse($this->input('date'));
                $currentStatement = $this->route('statement');

                $otherStatements = Statement::query()
                    ->when($currentStatement instanceof Statement, fn ($query) => $query->whereKeyNot($currentStatement->getKey()));

                if ($this->boolean('is_draft')) {
                    if ((clone $otherStatements)->where('is_draft', true)->exists()) {
                        $validator->errors()->add('is_draft', 'Only one draft is allowed.');

                        return;
                    }

                    $latestRealDate = (clone $otherStatements)->where('is_draft', false)->max('date');

                    if ($latestRealDate !== null && $date->lte(Carbon::parse($latestRealDate))) {
                        $validator->errors()->add('is_draft', 'A draft must be dated after the latest statement.');
                    }

                    return;
                }

                $draftDate = (clone $otherStatements)->where('is_draft', true)->value('date');

                if ($draftDate !== null && $date->gte(Carbon::parse($draftDate))) {
                    $validator->errors()->add('date', 'A statement must be dated before the existing draft.');
                }
            },
        ];
    }
}
