<?php

namespace App\Http\Controllers;

use App\Models\MonthlyBalance;
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

        return Inertia::render('Dashboard', [
            'monthlyAverages' => $monthlyAverages,
            'periodAverages' => $periodAverages,
        ]);
    }
}
