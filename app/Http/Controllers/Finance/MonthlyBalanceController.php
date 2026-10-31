<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\MonthlyBalanceData;
use App\Http\Controllers\Controller;
use App\Models\Finance\Asset;
use App\Models\Finance\Statement;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyBalanceController extends Controller
{
    public function index(): Response
    {
        $balances = MonthlyBalanceData::fromStatements(
            Statement::with('balances')->get(),
            Asset::where('tracks_monthly_balance', true)->first(),
        );

        return Inertia::render('finance/MonthlyBalance', [
            'balances' => $balances->reverse()->values(),
        ]);
    }
}
