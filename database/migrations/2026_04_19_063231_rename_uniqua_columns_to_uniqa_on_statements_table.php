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
        Schema::table('statements', function (Blueprint $table) {
            $table->renameColumn('uniqua_sds', 'uniqa_sds');
            $table->renameColumn('uniqua_dds', 'uniqa_dds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->renameColumn('uniqa_sds', 'uniqua_sds');
            $table->renameColumn('uniqa_dds', 'uniqua_dds');
        });
    }
};
