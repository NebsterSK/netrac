<?php

namespace App\Http\Controllers;

use App\Data\Finance\ExpenseCategoryBreakdownData;
use App\Data\Finance\MonthlyAverageData;
use App\Data\Finance\MonthlyBalanceData;
use App\Data\Finance\PeriodAveragesData;
use App\Models\Finance\ExpenseCategory;
use App\Models\Finance\Statement;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $balances = MonthlyBalanceData::fromStatements(Statement::all())
            ->reject(fn (MonthlyBalanceData $balance): bool => $balance->is_draft);

        $monthlyAverages = $balances
            ->groupBy(fn (MonthlyBalanceData $balance): int => (int) substr($balance->date, 5, 2))
            ->sortKeys()
            ->map(fn (Collection $group, int $month): array => [
                'month' => $month,
                'average' => (int) round($group->avg('amount')),
                'count' => $group->count(),
            ])
            ->values();

        $amounts = $balances->reverse()->pluck('amount')->values();

        $periodAverages = [
            'last6' => $amounts->take(6)->isEmpty() ? null : (int) round($amounts->take(6)->avg()),
            'last12' => $amounts->take(12)->isEmpty() ? null : (int) round($amounts->take(12)->avg()),
            'last18' => $amounts->take(18)->isEmpty() ? null : (int) round($amounts->take(18)->avg()),
            'overall' => $amounts->isEmpty() ? null : (int) round($amounts->avg()),
        ];

        $expenseCategories = ExpenseCategory::query()
            ->whereHas('expenses')
            ->with(['expenses' => fn ($query) => $query->orderByDesc('amount')->orderBy('name')])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return Inertia::render('Dashboard', [
            'monthlyAverages' => MonthlyAverageData::collect($monthlyAverages),
            'periodAverages' => PeriodAveragesData::from($periodAverages),
            'expenseCategories' => $expenseCategories
                ->map(fn (ExpenseCategory $category): ExpenseCategoryBreakdownData => ExpenseCategoryBreakdownData::fromCategory($category))
                ->values(),
        ]);
    }
}
