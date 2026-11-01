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
};
