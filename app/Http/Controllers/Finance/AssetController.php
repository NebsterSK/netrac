<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\AssetData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Asset\StoreAssetRequest;
use App\Http\Requests\Finance\Asset\UpdateAssetRequest;
use App\Models\Finance\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AssetController extends Controller
{
    public function index(): Response
    {
        $assets = Asset::withCount('statements')->orderBy('name')->get();

        return Inertia::render('finance/Asset', [
            'assets' => AssetData::collect($assets),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $asset = Asset::create($request->validated());

                $this->keepSingleMonthlyBalanceAsset($asset);
            });
        } catch (Throwable $error) {
            Log::error('Failed to create asset', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create asset.');
        }

        return to_route('finance.assets.index')->with('success', 'Asset created.');
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $asset): void {
                $asset->update($request->validated());

                $this->keepSingleMonthlyBalanceAsset($asset);
            });
        } catch (Throwable $error) {
            Log::error('Failed to update asset', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update asset.');
        }

        return to_route('finance.assets.index')->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        try {
            $asset->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete asset', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete asset.');
        }

        return to_route('finance.assets.index')->with('success', 'Asset deleted.');
    }

    /**
     * Only one asset drives the monthly balance, so marking this one unmarks the rest.
     */
    private function keepSingleMonthlyBalanceAsset(Asset $asset): void
    {
        if (! $asset->tracks_monthly_balance) {
            return;
        }

        Asset::whereKeyNot($asset->id)
            ->where('tracks_monthly_balance', true)
            ->update(['tracks_monthly_balance' => false]);
    }
}
