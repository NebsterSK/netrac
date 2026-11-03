<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foreign keys that cascaded on delete only: table, column, referenced table.
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    private array $foreignKeys = [
        ['buzerlistok_goals', 'week_id', 'buzerlistok_weeks'],
        ['buzerlistok_marks', 'goal_id', 'buzerlistok_goals'],
        ['asset_statement', 'statement_id', 'statements'],
        ['asset_statement', 'asset_id', 'assets'],
        ['exercise_workout_session', 'workout_session_id', 'workout_sessions'],
        ['exercise_workout_session', 'exercise_id', 'exercises'],
    ];

    /**
     * Run the migrations.
     *
     * Makes each key cascade on update as well as on delete.
     */
    public function up(): void
    {
        foreach ($this->foreignKeys as [$tableName, $column, $referenced]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $referenced) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on($referenced)->cascadeOnUpdate()->cascadeOnDelete();
            });
        }
    }
};
