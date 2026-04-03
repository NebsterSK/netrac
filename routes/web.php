<?php

use App\Http\Controllers\MonthlyBalanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
    Route::resource('/monthly-balance', MonthlyBalanceController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
