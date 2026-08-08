<?php

namespace App\Data\Buzerlistok;

use App\Models\Buzerlistok\Goal;
use App\Models\Buzerlistok\Mark;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class GoalData extends Data
{
    /**
     * @param  array<int, MarkData>  $marks
     */
    public function __construct(
        public int $id,
        public string $name,
        #[DataCollectionOf(MarkData::class)]
        public array $marks,
    ) {}

    public static function fromGoal(Goal $goal): self
    {
        return new self(
            id: $goal->id,
            name: $goal->name,
            marks: $goal->marks
                ->map(fn (Mark $mark): MarkData => MarkData::fromMark($mark))
                ->all(),
        );
    }
}
