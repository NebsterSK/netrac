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
        DB::table('buzerlistok_marks')->where('status', 'na')->delete();

        Schema::table('buzerlistok_marks', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
};
