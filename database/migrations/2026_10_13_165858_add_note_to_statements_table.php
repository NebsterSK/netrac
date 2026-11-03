<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Monthly balances are now derived from statements, so the comments that
     * explained them move onto the statement of the same month.
     */
    public function up(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->string('note')->nullable()->after('trading212');
        });

        $comments = DB::table('monthly_balances')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->get(['date', 'comment']);

        foreach ($comments as $balance) {
            DB::table('statements')
                ->whereDate('date', $balance->date)
                ->update(['note' => $balance->comment]);
        }
    }
};
