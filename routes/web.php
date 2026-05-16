<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\MonthlyBalanceController;
use App\Http\Controllers\NetWorthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('/monthly-balance', MonthlyBalanceController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/net-worth', NetWorthController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['net-worth' => 'statement']);
    Route::resource('/exercise', ExerciseController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
