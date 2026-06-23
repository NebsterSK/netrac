<?php

namespace App\Http\Controllers;

use App\Data\Finance\ExpenseCategoryTotalData;
use App\Data\Finance\MonthlyAverageData;
use App\Data\Finance\PeriodAveragesData;
use App\Models\Finance\Expense;
use App\Models\Finance\MonthlyBalance;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $monthlyAverages = MonthlyBalance::query()
            ->select(
                DB::raw('MONTH(date) as month'),
                DB::raw('ROUND(AVG(amount)) as average'),
                DB::raw('COUNT(*) as count'),
            )
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy('month')
            ->get();

        $balances = MonthlyBalance::orderBy('date', 'desc')->pluck('amount');

        $periodAverages = [
            'last6' => $balances->take(6)->isEmpty() ? null : (int) round($balances->take(6)->avg()),
            'last12' => $balances->take(12)->isEmpty() ? null : (int) round($balances->take(12)->avg()),
            'last18' => $balances->take(18)->isEmpty() ? null : (int) round($balances->take(18)->avg()),
            'overall' => $balances->isEmpty() ? null : (int) round($balances->avg()),
        ];

        $expenseCategoryTotals = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->select(
                'expense_categories.id as id',
                'expense_categories.name as name',
                DB::raw('SUM(expenses.amount) as total'),
                DB::raw('COUNT(*) as count'),
            )
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('Dashboard', [
            'monthlyAverages' => MonthlyAverageData::collect($monthlyAverages),
            'periodAverages' => PeriodAveragesData::from($periodAverages),
            'expenseCategoryTotals' => ExpenseCategoryTotalData::collect($expenseCategoryTotals),
            'expenseTotal' => (int) $expenseCategoryTotals->sum('total'),
        ]);
    }
}
