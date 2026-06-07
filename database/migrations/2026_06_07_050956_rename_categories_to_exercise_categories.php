<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('categories', 'exercise_categories');

        Schema::table('exercise_categories', function (Blueprint $table) {
            $table->integer('priority')->default(1)->after('name');
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->renameColumn('category_id', 'exercise_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->renameColumn('exercise_category_id', 'category_id');
        });

        Schema::table('exercise_categories', function (Blueprint $table) {
            $table->dropColumn('priority');
        });

        Schema::rename('exercise_categories', 'categories');
    }
};
