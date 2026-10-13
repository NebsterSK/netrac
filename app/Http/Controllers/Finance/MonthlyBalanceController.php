<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\MonthlyBalanceData;
use App\Http\Controllers\Controller;
use App\Models\Finance\Statement;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyBalanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('finance/MonthlyBalance', [
            'balances' => MonthlyBalanceData::fromStatements(Statement::all())->reverse()->values(),
        ]);
    }
}
