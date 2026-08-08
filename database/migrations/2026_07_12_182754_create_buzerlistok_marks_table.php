<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('buzerlistok_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('buzerlistok_goals')->cascadeOnDelete();
            $table->date('marked_on');
            $table->string('status');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['goal_id', 'marked_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buzerlistok_marks');
    }
};
