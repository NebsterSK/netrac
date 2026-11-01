<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds the Shoulders category. Hardcoded to keep the migration
     * self-contained (no enum/model references). Priority 3 keeps Cardio alone
     * at the lowest tier.
     */
    public function up(): void
    {
        $timestamp = now();

        DB::table('exercise_categories')->insertOrIgnore([
            'name' => 'Shoulders',
            'priority' => 3,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
};
