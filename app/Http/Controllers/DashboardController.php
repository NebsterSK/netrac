<?php

namespace App\Http\Controllers;

use App\Data\Buzerlistok\WeekData;
use App\Data\Finance\ExpenseCategoryBreakdownData;
use App\Data\Finance\MonthlyBalanceData;
use App\Data\Finance\PeriodAveragesData;
use App\Data\Health\WeightData;
use App\Models\Buzerlistok\Week;
use App\Models\Finance\ExpenseCategory;
use App\Models\Health\Weight;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /** How many of the latest Buzerlistok weeks the success-rate chart shows. */
    private const int BUZERLISTOK_CHART_WEEKS = 10;

    /** How many of the latest weight entries the weight chart shows. */
    private const int WEIGHT_CHART_ENTRIES = 5;

    public function __invoke(Request $request): Response
    {
        $ownerFilter = $request->user()->financeOwnerFilter();

        $balances = MonthlyBalanceData::forOwnerFilter($ownerFilter)
            ->reject(fn (MonthlyBalanceData $balance): bool => $balance->is_draft);

        $amounts = $balances->reverse()->pluck('amount')->values();

        $periodAverages = [
            'last6' => $amounts->take(6)->isEmpty() ? null : (int) round($amounts->take(6)->avg()),
            'last12' => $amounts->take(12)->isEmpty() ? null : (int) round($amounts->take(12)->avg()),
            'last18' => $amounts->take(18)->isEmpty() ? null : (int) round($amounts->take(18)->avg()),
            'overall' => $amounts->isEmpty() ? null : (int) round($amounts->avg()),
        ];

        $expenseCategories = ExpenseCategory::query()
            ->whereHas('expenses', fn ($query) => $query->ownedBy($ownerFilter))
            ->with(['expenses' => fn ($query) => $query->ownedBy($ownerFilter)->orderByDesc('amount')->orderBy('name')])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        $buzerlistokWeeks = Week::with('goals.marks')
            ->orderByDesc('starts_on')
            ->limit(self::BUZERLISTOK_CHART_WEEKS)
            ->get()
            ->reverse()
            ->map(fn (Week $week): WeekData => WeekData::fromWeek($week))
            ->values();

        $latestWeights = Weight::orderByDesc('date')
            ->limit(self::WEIGHT_CHART_ENTRIES)
            ->get()
            ->reverse()
            ->map(fn (Weight $weight): WeightData => WeightData::fromWeight($weight))
            ->values();

        return Inertia::render('Dashboard', [
            'periodAverages' => PeriodAveragesData::from($periodAverages),
            'expenseCategories' => $expenseCategories
                ->map(fn (ExpenseCategory $category): ExpenseCategoryBreakdownData => ExpenseCategoryBreakdownData::fromCategory($category))
                ->values(),
            'buzerlistokWeeks' => $buzerlistokWeeks,
            'latestWeights' => $latestWeights,
        ]);
    }
}
