<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Default priority per canonical category (1 = highest). Hardcoded to keep
     * the migration self-contained (no enum/model references).
     *
     * @var array<string, int>
     */
    private array $priorities = [
        'Arms' => 1,
        'Chest' => 1,
        'Legs' => 2,
        'Back' => 2,
        'Core' => 3,
        'Cardio' => 4,
    ];

    public function up(): void
    {
        foreach ($this->priorities as $name => $priority) {
            DB::table('exercise_categories')->where('name', $name)->update(['priority' => $priority]);
        }
    }

    public function down(): void
    {
        DB::table('exercise_categories')
            ->whereIn('name', array_keys($this->priorities))
            ->update(['priority' => 1]);
    }
};
