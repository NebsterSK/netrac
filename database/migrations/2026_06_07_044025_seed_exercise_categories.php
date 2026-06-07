<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Canonical exercise categories that must always be available.
     * Kept in sync by hand with the App\Enums\Health\ExerciseCategory enum
     * (migrations stay self-contained and must not reference app classes).
     *
     * @var list<string>
     */
    private array $categories = ['Chest', 'Legs', 'Back', 'Core', 'Cardio', 'Arms'];

    public function up(): void
    {
        $timestamp = now();

        $rows = array_map(fn (string $name): array => [
            'name' => $name,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $this->categories);

        DB::table('categories')->insertOrIgnore($rows);
    }

    public function down(): void
    {
        DB::table('categories')->whereIn('name', $this->categories)->delete();
    }
};
