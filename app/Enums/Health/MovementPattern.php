<?php

namespace App\Enums\Health;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum MovementPattern: string
{
    case Push = 'Push';
    case Pull = 'Pull';
    case Squat = 'Squat';
    case Hinge = 'Hinge';
}
