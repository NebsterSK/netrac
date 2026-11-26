<?php

namespace App\Enums\Buzerlistok;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * How a day turned out across a week's daily goals, before deciding whether an
 * unfinished day is still open (today) or missed (in the past).
 */
#[TypeScript]
enum DayResult: string
{
    case Success = 'success';
    case Fail = 'fail';
    case Incomplete = 'incomplete';
}
