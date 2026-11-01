<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The hardcoded balance columns, in their table order, with the asset each becomes.
     *
     * @var array<int, array{column: string, name: string, color: string, tracks_monthly_balance: bool}>
     */
    private array $assets = [
        ['column' => 'account', 'name' => 'Account', 'color' => '#9ca3af', 'tracks_monthly_balance' => true],
        ['column' => 'legacy_upgrade', 'name' => 'Legacy Upgrade', 'color' => '#0559d2', 'tracks_monthly_balance' => false],
        ['column' => 'uniqa_sds', 'name' => 'Uniqa SDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
        ['column' => 'uniqa_dds', 'name' => 'Uniqa DDS', 'color' => '#005ca9', 'tracks_monthly_balance' => false],
        ['column' => 'finax', 'name' => 'Finax', 'color' => '#cbe7ff', 'tracks_monthly_balance' => false],
        ['column' => 'trading212', 'name' => 'Trading 212', 'color' => '#00abe0', 'tracks_monthly_balance' => false],
    ];

    /**
     * Run the migrations.
     *
     * Turns each hardcoded balance column on statements into an asset row and
     * moves every statement's amounts into the asset_statement table.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 7)->nullable();
            $table->boolean('tracks_monthly_balance')->default(false);
            $table->timestamps();
        });

        Schema::create('asset_statement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->timestamps();

            $table->unique(['statement_id', 'asset_id']);
        });

        $now = now();

        foreach ($this->assets as $asset) {
            $assetId = DB::table('assets')->insertGetId([
                'name' => $asset['name'],
                'color' => $asset['color'],
                'tracks_monthly_balance' => $asset['tracks_monthly_balance'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('asset_statement')->insertUsing(
                ['statement_id', 'asset_id', 'amount', 'created_at', 'updated_at'],
                DB::table('statements')
                    ->select('id')
                    ->selectRaw('? as asset_id', [$assetId])
                    ->addSelect($asset['column'], 'created_at', 'updated_at'),
            );
        }

        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn(array_column($this->assets, 'column'));
        });
    }
};
