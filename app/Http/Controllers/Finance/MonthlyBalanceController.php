<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\MonthlyBalanceData;
use App\Data\Finance\MonthlyBalancePayloadData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\MonthlyBalance\StoreMonthlyBalanceRequest;
use App\Http\Requests\Finance\MonthlyBalance\UpdateMonthlyBalanceRequest;
use App\Models\Finance\MonthlyBalance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MonthlyBalanceController extends Controller
{
    public function index(): Response
    {
        $balances = MonthlyBalance::orderBy('date', 'desc')->get();

        return Inertia::render('finance/MonthlyBalance', [
            'balances' => MonthlyBalanceData::collect($balances),
            'existingDates' => $balances->pluck('date')->toArray(),
        ]);
    }

    public function store(StoreMonthlyBalanceRequest $request): RedirectResponse
    {
        try {
            MonthlyBalance::create(MonthlyBalancePayloadData::from($request->validated())->toArray());
        } catch (Throwable $error) {
            Log::error('Failed to create balance', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create balance.');
        }

        return to_route('finance.monthly-balance.index')->with('success', 'Balance created.');
    }

    public function update(UpdateMonthlyBalanceRequest $request, MonthlyBalance $monthlyBalance): RedirectResponse
    {
        try {
            $monthlyBalance->update(MonthlyBalancePayloadData::from($request->validated())->toArray());
        } catch (Throwable $error) {
            Log::error('Failed to update balance', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update balance.');
        }

        return to_route('finance.monthly-balance.index')->with('success', 'Balance updated.');
    }

    public function destroy(MonthlyBalance $monthlyBalance): RedirectResponse
    {
        try {
            $monthlyBalance->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete balance', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete balance.');
        }

        return to_route('finance.monthly-balance.index')->with('success', 'Balance deleted.');
    }
}
