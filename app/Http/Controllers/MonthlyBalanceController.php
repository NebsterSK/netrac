<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthlyBalance\StoreMonthlyBalanceRequest;
use App\Http\Requests\MonthlyBalance\UpdateMonthlyBalanceRequest;
use App\Models\MonthlyBalance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MonthlyBalanceController extends Controller
{
    public function index(): Response
    {
        // TODO: Limit 35?
        $balances = MonthlyBalance::orderBy('date', 'desc')->get();

        return Inertia::render('MonthlyBalance', [
            'balances' => $balances,
            'existingDates' => $balances->pluck('date')->toArray(),
        ]);
    }

    public function store(StoreMonthlyBalanceRequest $request): RedirectResponse
    {
        try {
            MonthlyBalance::create($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to create balance', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create balance.');
        }

        return to_route('monthly-balance.index')->with('success', 'Balance created.');
    }

    public function update(UpdateMonthlyBalanceRequest $request, MonthlyBalance $monthlyBalance): RedirectResponse
    {
        try {
            $monthlyBalance->update($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to update balance', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update balance.');
        }

        return to_route('monthly-balance.index')->with('success', 'Balance updated.');
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

        return to_route('monthly-balance.index')->with('success', 'Balance deleted.');
    }
}
