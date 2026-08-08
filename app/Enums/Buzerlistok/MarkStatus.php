<?php

namespace App\Enums\Buzerlistok;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum MarkStatus: string
{
    case Success = 'success';
    case Partial = 'partial';
    case NotApplicable = 'na';
    case Fail = 'fail';
}
