<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\MonthlyBalanceController;
use App\Http\Controllers\Finance\NetWorthController;
use App\Http\Controllers\Health\CategoryController;
use App\Http\Controllers\Health\ExerciseController;
use App\Http\Controllers\Health\SessionController;
use App\Models\Health\WorkoutSession;
use Illuminate\Support\Facades\Route;

Route::model('session', WorkoutSession::class);

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::resource('/monthly-balance', MonthlyBalanceController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('/net-worth', NetWorthController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['net-worth' => 'statement']);
    });

    Route::prefix('health')->name('health.')->group(function () {
        Route::resource('/exercises', ExerciseController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('/categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('/sessions', SessionController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::patch('/sessions/{session}/exercises/{exercise}', [SessionController::class, 'updateExercise'])->name('sessions.exercises.update');
    });
});

require __DIR__.'/settings.php';
