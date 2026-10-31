<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The hardcoded balance columns, in their table order, with the vault each becomes.
     *
     * @var array<int, array{column: string, name: string, color: string, tracks_monthly_balance: bool}>
     */
    private array $vaults = [
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
     * Turns each hardcoded balance column on statements into a vault row and
     * moves every statement's amounts into the statement_vault table.
     */
    public function up(): void
    {
        Schema::create('vaults', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 7)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('tracks_monthly_balance')->default(false);
            $table->timestamps();
        });

        Schema::create('statement_vault', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vault_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->unique(['statement_id', 'vault_id']);
        });

        $now = now();

        foreach ($this->vaults as $index => $vault) {
            $vaultId = DB::table('vaults')->insertGetId([
                'name' => $vault['name'],
                'color' => $vault['color'],
                'position' => $index + 1,
                'tracks_monthly_balance' => $vault['tracks_monthly_balance'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('statement_vault')->insertUsing(
                ['statement_id', 'vault_id', 'amount'],
                DB::table('statements')->select('id', DB::raw((int) $vaultId), $vault['column']),
            );
        }

        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn(array_column($this->vaults, 'column'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores the hardcoded columns from the original six vaults. Amounts of
     * vaults added later are lost.
     */
    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            foreach (array_reverse($this->vaults) as $vault) {
                $table->integer($vault['column'])->default(0)->after('date');
            }
        });

        foreach ($this->vaults as $vault) {
            $vaultId = DB::table('vaults')->where('name', $vault['name'])->value('id');

            if ($vaultId === null) {
                continue;
            }

            $amounts = DB::table('statement_vault')
                ->where('vault_id', $vaultId)
                ->pluck('amount', 'statement_id');

            foreach ($amounts as $statementId => $amount) {
                DB::table('statements')->where('id', $statementId)->update([$vault['column'] => $amount]);
            }
        }

        Schema::dropIfExists('statement_vault');
        Schema::dropIfExists('vaults');
    }
};
