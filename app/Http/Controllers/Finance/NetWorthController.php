<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\StatementData;
use App\Data\Finance\VaultData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Statement\StoreStatementRequest;
use App\Http\Requests\Finance\Statement\UpdateStatementRequest;
use App\Models\Finance\Statement;
use App\Models\Finance\Vault;
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
        $statements = Statement::with('vaults')->orderBy('date', 'desc')->get();

        return Inertia::render('finance/NetWorth', [
            'vaults' => VaultData::collect(Vault::orderBy('position')->orderBy('id')->get()),
            'statements' => StatementData::collect($statements),
            'existingDates' => $statements->pluck('date')->toArray(),
        ]);
    }

    public function store(StoreStatementRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $statement = Statement::create($request->safe()->except('amounts'));

                $statement->vaults()->sync($this->vaultAmounts($request->validated('amounts')));
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
                $statement->update($request->safe()->except('amounts'));

                $statement->vaults()->sync($this->vaultAmounts($request->validated('amounts')));
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
     * Shape validated amounts (keyed by vault id) as pivot attributes for sync().
     *
     * @param  array<int, int>  $amounts
     * @return array<int, array{amount: int}>
     */
    private function vaultAmounts(array $amounts): array
    {
        return collect($amounts)
            ->map(fn (int|string $amount): array => ['amount' => (int) $amount])
            ->all();
    }
}
