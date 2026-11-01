<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\MonthlyBalanceData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyBalanceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('finance/MonthlyBalance', [
            'balances' => MonthlyBalanceData::forOwnerFilter($request->user()->financeOwnerFilter())->reverse()->values(),
        ]);
    }
}
