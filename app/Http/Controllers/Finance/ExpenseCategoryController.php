<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\ExpenseCategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ExpenseCategory\ReorderExpenseCategoriesRequest;
use App\Http\Requests\Finance\ExpenseCategory\StoreExpenseCategoryRequest;
use App\Http\Requests\Finance\ExpenseCategory\UpdateExpenseCategoryRequest;
use App\Models\Finance\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ExpenseCategoryController extends Controller
{
    public function index(): Response
    {
        $expenseCategories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();

        return Inertia::render('finance/ExpenseCategory', [
            'categories' => ExpenseCategoryData::collect($expenseCategories),
        ]);
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        try {
            ExpenseCategory::create([
                ...$request->validated(),
                'position' => (int) ExpenseCategory::max('position') + 1,
            ]);
        } catch (Throwable $error) {
            Log::error('Failed to create expense category', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create category.');
        }

        return to_route('finance.expense-categories.index')->with('success', 'Category created.');
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        try {
            $expenseCategory->update($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to update expense category', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update category.');
        }

        return to_route('finance.expense-categories.index')->with('success', 'Category updated.');
    }

    public function reorder(ReorderExpenseCategoriesRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                foreach ($request->validated('ids') as $index => $id) {
                    ExpenseCategory::whereKey($id)->update(['position' => $index + 1]);
                }
            });
        } catch (Throwable $error) {
            Log::error('Failed to reorder expense categories', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to reorder categories.');
        }

        return back();
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        try {
            $expenseCategory->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete expense category', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete category.');
        }

        return to_route('finance.expense-categories.index')->with('success', 'Category deleted.');
    }
}
