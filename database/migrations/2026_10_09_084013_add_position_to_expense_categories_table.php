<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('name');
        });

        DB::table('expense_categories')
            ->orderBy('name')
            ->pluck('id')
            ->each(fn (int $id, int $index) => DB::table('expense_categories')
                ->where('id', $id)
                ->update(['position' => $index + 1]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
