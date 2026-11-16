<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Weekly goals belong to the week rather than to a day: each has a target
     * number of times and a running count of how often it was done.
     */
    public function up(): void
    {
        Schema::create('buzerlistok_weekly_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('week_id')->constrained('buzerlistok_weeks')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('target');
            $table->unsignedTinyInteger('completions')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }
};
