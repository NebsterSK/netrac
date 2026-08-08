<?php

namespace App\Data\Buzerlistok;

use App\Enums\Buzerlistok\MarkStatus;
use App\Models\Buzerlistok\Mark;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MarkData extends Data
{
    public function __construct(
        public string $marked_on,
        public MarkStatus $status,
        public ?string $comment,
    ) {}

    public static function fromMark(Mark $mark): self
    {
        return new self(
            marked_on: $mark->marked_on->toDateString(),
            status: $mark->status,
            comment: $mark->comment,
        );
    }
}
