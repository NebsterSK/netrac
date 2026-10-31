<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The hardcoded statement columns, in their former display order.
     *
     * @var array<string, array{name: string, color: string, tracks_monthly_balance: bool}>
     */
    private array $legacyColumns = [
        'account' => ['name' => 'Account', 'color' => '#9ca3af', 'tracks_monthly_balance' => true],
        'legacy_upgrade' => ['name' => 'Legacy Upgrade', 'color' => '#0559d2', 'tracks_monthly_balance' => false],
        'uniqa_sds' => ['name' => 'Uniqa SDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
        'uniqa_dds' => ['name' => 'Uniqa DDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
        'finax' => ['name' => 'Finax', 'color' => '#cbe7ff', 'tracks_monthly_balance' => false],
        'trading212' => ['name' => 'Trading 212', 'color' => '#00abe0', 'tracks_monthly_balance' => false],
    ];

    /**
     * Run the migrations.
     *
     * Each hardcoded statement column becomes an asset row, and every
     * statement's amounts move into statement_balances.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 7)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('tracks_monthly_balance')->default(false);
            $table->timestamps();
        });

        Schema::create('statement_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->timestamps();

            $table->unique(['statement_id', 'asset_id']);
        });

        $now = now();
        $position = 0;

        foreach ($this->legacyColumns as $column => $asset) {
            $assetId = DB::table('assets')->insertGetId([
                ...$asset,
                'position' => ++$position,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('statement_balances')->insertUsing(
                ['statement_id', 'asset_id', 'amount', 'created_at', 'updated_at'],
                DB::table('statements')
                    ->select('id')
                    ->selectRaw('? as asset_id', [$assetId])
                    ->addSelect($column, 'created_at', 'updated_at'),
            );
        }

        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn(array_keys($this->legacyColumns));
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores the hardcoded columns from the matching assets (matched by
     * name); balances of any other asset are lost.
     */
    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            foreach (array_reverse(array_keys($this->legacyColumns)) as $column) {
                $table->integer($column)->default(0)->after('date');
            }
        });

        foreach ($this->legacyColumns as $column => $asset) {
            $assetId = DB::table('assets')->where('name', $asset['name'])->value('id');

            if ($assetId === null) {
                continue;
            }

            DB::table('statements')->update([
                $column => DB::raw('coalesce((select amount from statement_balances where statement_balances.statement_id = statements.id and statement_balances.asset_id = '.(int) $assetId.'), 0)'),
            ]);
        }

        Schema::dropIfExists('statement_balances');
        Schema::dropIfExists('assets');
    }
};
