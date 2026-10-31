<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\VaultData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Vault\ReorderVaultsRequest;
use App\Http\Requests\Finance\Vault\StoreVaultRequest;
use App\Http\Requests\Finance\Vault\UpdateVaultRequest;
use App\Models\Finance\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class VaultController extends Controller
{
    public function index(): Response
    {
        $vaults = Vault::withCount('statements')->orderBy('position')->orderBy('id')->get();

        return Inertia::render('finance/Vault', [
            'vaults' => VaultData::collect($vaults),
        ]);
    }

    public function store(StoreVaultRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $vault = Vault::create([
                    ...$request->validated(),
                    'position' => (int) Vault::max('position') + 1,
                ]);

                $this->keepSingleMonthlyBalanceVault($vault);
            });
        } catch (Throwable $error) {
            Log::error('Failed to create vault', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create vault.');
        }

        return to_route('finance.vaults.index')->with('success', 'Vault created.');
    }

    public function update(UpdateVaultRequest $request, Vault $vault): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $vault): void {
                $vault->update($request->validated());

                $this->keepSingleMonthlyBalanceVault($vault);
            });
        } catch (Throwable $error) {
            Log::error('Failed to update vault', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update vault.');
        }

        return to_route('finance.vaults.index')->with('success', 'Vault updated.');
    }

    public function reorder(ReorderVaultsRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                foreach ($request->validated('ids') as $index => $id) {
                    Vault::whereKey($id)->update(['position' => $index + 1]);
                }
            });
        } catch (Throwable $error) {
            Log::error('Failed to reorder vaults', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to reorder vaults.');
        }

        return back();
    }

    public function destroy(Vault $vault): RedirectResponse
    {
        try {
            $vault->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete vault', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete vault.');
        }

        return to_route('finance.vaults.index')->with('success', 'Vault deleted.');
    }

    /**
     * Only one vault drives the monthly balance, so marking this one unmarks the rest.
     */
    private function keepSingleMonthlyBalanceVault(Vault $vault): void
    {
        if (! $vault->tracks_monthly_balance) {
            return;
        }

        Vault::whereKeyNot($vault->id)
            ->where('tracks_monthly_balance', true)
            ->update(['tracks_monthly_balance' => false]);
    }
}
