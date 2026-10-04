<?php

use App\Http\Controllers\Buzerlistok\MarkController;
use App\Http\Controllers\Buzerlistok\WeekController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\ExpenseCategoryController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\MonthlyBalanceController;
use App\Http\Controllers\Finance\NetWorthController;
use App\Http\Controllers\Health\ExerciseCategoryController;
use App\Http\Controllers\Health\ExerciseController;
use App\Http\Controllers\Health\SessionController;
use App\Models\Health\WorkoutSession;
use Illuminate\Support\Facades\Route;

Route::model('session', WorkoutSession::class);

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('buzerlistok')->name('buzerlistok.')->group(function () {
        Route::get('/', [WeekController::class, 'index'])->name('index');
        Route::post('/', [WeekController::class, 'store'])->name('store');
        Route::get('/{week}/edit', [WeekController::class, 'edit'])->name('edit');
        Route::put('/{week}', [WeekController::class, 'update'])->name('update');
        Route::delete('/{week}', [WeekController::class, 'destroy'])->name('destroy');
        Route::put('/goals/{goal}/marks', [MarkController::class, 'update'])->name('goals.marks.update');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::resource('/monthly-balance', MonthlyBalanceController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('/net-worth', NetWorthController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['net-worth' => 'statement']);
        Route::resource('/expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('/expense-categories', ExpenseCategoryController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['expense-categories' => 'expenseCategory']);
    });

    Route::prefix('health')->name('health.')->group(function () {
        Route::resource('/exercises', ExerciseController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('/exercise-categories', ExerciseCategoryController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['exercise-categories' => 'exerciseCategory']);
        Route::patch('/exercise-categories/{exerciseCategory}/priority', [ExerciseCategoryController::class, 'updatePriority'])->name('exercise-categories.priority.update');
        Route::resource('/sessions', SessionController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::patch('/sessions/{session}/exercises/{exercise}', [SessionController::class, 'updateExercise'])->name('sessions.exercises.update');
    });
});

require __DIR__.'/settings.php';
