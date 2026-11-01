<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = ['assets', 'expenses', 'statements'];

    /**
     * Run the migrations.
     *
     * Gives every asset, expense and statement an owner; existing rows go to
     * the first user. Without any user there is nobody to own them, so the
     * rows (only ever the default assets, see ensureMigratable()) are removed.
     */
    public function up(): void
    {
        $this->ensureMigratable();

        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });
        }

        $ownerId = DB::table('users')->orderBy('id')->value('id');

        foreach ($this->tables as $tableName) {
            if ($ownerId === null) {
                DB::table($tableName)->delete();
            } else {
                DB::table($tableName)->update(['user_id' => $ownerId]);
            }
        }

        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            });
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['user_id', 'name']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['user_id', 'name']);
        });

        Schema::table('statements', function (Blueprint $table) {
            $table->dropUnique(['date']);
            $table->char('month', 7)->storedAs("date_format(date, '%Y-%m')")->after('date');
            $table->unique(['user_id', 'month']);
        });
    }

    /**
     * Refuse to run before any DDL when the data can't be migrated safely:
     * real statements or expenses without a user to own them, or a month with
     * more than one statement (it would break the per-owner month key).
     *
     * @throws RuntimeException
     */
    private function ensureMigratable(): void
    {
        $hasUser = DB::table('users')->exists();

        if (! $hasUser && (DB::table('statements')->exists() || DB::table('expenses')->exists())) {
            throw new RuntimeException('Cannot assign existing statements and expenses to an owner: create a user first, then rerun the migration.');
        }

        $duplicateMonths = DB::table('statements')
            ->selectRaw("date_format(date, '%Y-%m') as statement_month")
            ->groupBy('statement_month')
            ->havingRaw('count(*) > 1')
            ->pluck('statement_month');

        if ($duplicateMonths->isNotEmpty()) {
            throw new RuntimeException('Cannot migrate statements: these months have more than one statement: '.$duplicateMonths->implode(', ').'. Merge or delete the extras, then rerun the migration.');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @throws RuntimeException when multi-user data would break the restored single-owner unique keys
     */
    public function down(): void
    {
        $this->ensureReversible();

        Schema::table('statements', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'month']);
            $table->dropColumn(['month', 'user_id']);
            $table->unique('date');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'name']);
            $table->dropColumn('user_id');
            $table->unique('name');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'name']);
            $table->dropColumn('user_id');
            $table->unique('name');
        });
    }

    /**
     * Refuse to roll back before any DDL when the restored unique keys
     * (statements.date, assets.name, expenses.name) would be violated by rows
     * that only differ by owner.
     *
     * @throws RuntimeException
     */
    private function ensureReversible(): void
    {
        $conflicts = [];

        $duplicateMonths = DB::table('statements')
            ->select('month')
            ->groupBy('month')
            ->havingRaw('count(*) > 1')
            ->pluck('month');

        if ($duplicateMonths->isNotEmpty()) {
            $conflicts[] = 'statement months held by several owners ('.$duplicateMonths->implode(', ').')';
        }

        foreach (['assets', 'expenses'] as $tableName) {
            $duplicateNames = DB::table($tableName)
                ->select('name')
                ->groupBy('name')
                ->havingRaw('count(*) > 1')
                ->pluck('name');

            if ($duplicateNames->isNotEmpty()) {
                $conflicts[] = "{$tableName} names used by several owners (".$duplicateNames->implode(', ').')';
            }
        }

        if ($conflicts !== []) {
            throw new RuntimeException('Cannot roll back to single-owner finance tables: the data is multi-user and would violate the restored unique keys: '.implode('; ', $conflicts).'. Remove or rename the duplicates first.');
        }
    }
};
