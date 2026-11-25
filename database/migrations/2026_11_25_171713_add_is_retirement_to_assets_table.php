<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A label for retirement savings that can't be withdrawn or sold before
     * retirement age. It changes no calculations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->boolean('is_retirement')->default(false)->after('tracks_monthly_balance');
        });
    }
};
