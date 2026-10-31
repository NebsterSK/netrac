<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\MonthlyBalanceData;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyBalanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('finance/MonthlyBalance', [
            'balances' => MonthlyBalanceData::fromAllStatements()->reverse()->values(),
        ]);
    }
}
