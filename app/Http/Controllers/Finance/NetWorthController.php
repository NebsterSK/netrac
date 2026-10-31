<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\AssetData;
use App\Data\Finance\StatementData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Statement\StoreStatementRequest;
use App\Http\Requests\Finance\Statement\UpdateStatementRequest;
use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class NetWorthController extends Controller
{
    public function index(): Response
    {
        $statements = Statement::with('balances')->orderBy('date', 'desc')->get();

        return Inertia::render('finance/NetWorth', [
            'assets' => AssetData::collect(Asset::orderBy('position')->orderBy('id')->get()),
            'statements' => StatementData::collect($statements),
            'existingDates' => $statements->pluck('date')->toArray(),
        ]);
    }

    public function store(StoreStatementRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $statement = Statement::create($request->safe()->except('balances'));

                $this->saveBalances($statement, $request->validated('balances'));
            });
        } catch (Throwable $error) {
            Log::error('Failed to create statement', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create statement.');
        }

        return to_route('finance.net-worth.index')->with('success', 'Statement created.');
    }

    public function update(UpdateStatementRequest $request, Statement $statement): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $statement): void {
                $statement->update($request->safe()->except('balances'));

                $this->saveBalances($statement, $request->validated('balances'));
            });
        } catch (Throwable $error) {
            Log::error('Failed to update statement', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update statement.');
        }

        return to_route('finance.net-worth.index')->with('success', 'Statement updated.');
    }

    public function destroy(Statement $statement): RedirectResponse
    {
        try {
            $statement->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete statement', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete statement.');
        }

        return to_route('finance.net-worth.index')->with('success', 'Statement deleted.');
    }

    /**
     * @param  array<int, int>  $balances  amount keyed by asset id
     */
    private function saveBalances(Statement $statement, array $balances): void
    {
        foreach ($balances as $assetId => $amount) {
            $statement->balances()->updateOrCreate(['asset_id' => $assetId], ['amount' => $amount]);
        }
    }
}
