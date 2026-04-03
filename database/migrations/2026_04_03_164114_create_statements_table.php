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
        Schema::create('statements', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('account');
            $table->integer('legacy_upgrade');
            $table->integer('uniqua_sds');
            $table->integer('uniqua_dds');
            $table->integer('finax');
            $table->integer('trading212');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statements');
    }
};
