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

        return Inertia::render('Dashboard', [
            'monthlyAverages' => $monthlyAverages,
        ]);
    }
}
