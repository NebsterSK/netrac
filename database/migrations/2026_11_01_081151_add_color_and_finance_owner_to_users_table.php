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
        Schema::table('users', function (Blueprint $table) {
            $table->string('color', 7)->default('#6b7280')->after('email');
            $table->foreignId('finance_owner_id')->nullable()->after('color')
                ->constrained('users')->cascadeOnUpdate()->nullOnDelete();
        });
    }
};
