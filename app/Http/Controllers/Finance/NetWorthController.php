<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\AssetData;
use App\Data\Finance\NetWorthMonthData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Statement\StoreStatementRequest;
use App\Http\Requests\Finance\Statement\UpdateStatementRequest;
use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class NetWorthController extends Controller
{
    public function index(Request $request): Response
    {
        $ownerFilter = $request->user()->financeOwnerFilter();

        return Inertia::render('finance/NetWorth', [
            'assets' => AssetData::collect(Asset::query()->ownedBy($ownerFilter)->orderBy('name')->get()),
            'months' => NetWorthMonthData::forOwnerFilter($ownerFilter),
            'existingMonths' => $this->existingMonths(),
        ]);
    }

    public function store(StoreStatementRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $statement = Statement::create($request->safe()->except('amounts'));

                $statement->assets()->sync($this->assetAmounts($request->validated('amounts')));
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

                $statement->assets()->sync($this->assetAmounts($request->validated('amounts')));
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
     * Months each owner already has a statement for, across all owners, since
     * a new statement can be created for any owner.
     *
     * @return array<int, array<int, string>> 'YYYY-MM' months keyed by user id
     */
    private function existingMonths(): array
    {
        return Statement::query()
            ->orderBy('month')
            ->get(['user_id', 'month'])
            ->groupBy('user_id')
            ->map(fn (Collection $statements): array => $statements->pluck('month')->all())
            ->all();
    }

    /**
     * Shape validated amounts (keyed by asset id) as pivot attributes for sync().
     *
     * @param  array<int, int>  $amounts
     * @return array<int, array{amount: int}>
     */
    private function assetAmounts(array $amounts): array
    {
        return collect($amounts)
            ->map(fn (int|string $amount): array => ['amount' => (int) $amount])
            ->all();
    }
}
