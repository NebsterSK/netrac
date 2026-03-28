<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthlyBalance\StoreMonthlyBalanceRequest;
use App\Http\Requests\MonthlyBalance\UpdateMonthlyBalanceRequest;
use App\Models\MonthlyBalance;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyBalanceController extends Controller
{
    public function index(): Response
    {
        $existingDates = MonthlyBalance::orderBy('date', 'desc')->get();
        $balances = $existingDates->take(18);

        return Inertia::render('MonthlyBalance', [
            'balances' => $balances,
            'existingDates' => $existingDates->pluck('date')->toArray(),
        ]);
    }

    public function store(StoreMonthlyBalanceRequest $request): RedirectResponse
    {
        MonthlyBalance::create($request->validated());

        return to_route('monthly-balance.index');
    }

    public function update(UpdateMonthlyBalanceRequest $request, MonthlyBalance $monthlyBalance): RedirectResponse
    {
        $monthlyBalance->update($request->validated());

        return to_route('monthly-balance.index');
    }

    public function destroy(MonthlyBalance $monthlyBalance): RedirectResponse
    {
        $monthlyBalance->delete();

        return to_route('monthly-balance.index');
    }
}
