<?php

namespace App\Http\Controllers\Finance;

use App\Data\Finance\ExpenseCategoryData;
use App\Data\Finance\ExpenseData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Expense\IndexExpenseRequest;
use App\Http\Requests\Finance\Expense\StoreExpenseRequest;
use App\Http\Requests\Finance\Expense\UpdateExpenseRequest;
use App\Models\Finance\Expense;
use App\Models\Finance\ExpenseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class ExpenseController extends Controller
{
    public function index(IndexExpenseRequest $request): Response
    {
        $expenses = QueryBuilder::for(Expense::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('expense_category_id'),
            )
            ->allowedSorts(
                'name',
                'amount',
                'created_at',
                'updated_at',
                AllowedSort::callback('category', function (Builder $query, bool $descending): void {
                    $query->orderBy(
                        ExpenseCategory::query()
                            ->select('name')
                            ->whereColumn('expense_categories.id', 'expenses.expense_category_id'),
                        $descending ? 'desc' : 'asc',
                    );
                }),
            )
            ->defaultSort('-amount')
            ->with('expenseCategory')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('finance/Expense', [
            'expenses' => ExpenseData::collect($expenses->getCollection()),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
                'from' => $expenses->firstItem(),
                'to' => $expenses->lastItem(),
            ],
            'categories' => ExpenseCategoryData::collect(ExpenseCategory::orderBy('name')->get()),
            'filters' => [
                'name' => $request->string('filter.name')->toString(),
                'expense_category_ids' => array_values(array_filter(
                    explode(',', (string) $request->input('filter.expense_category_id', '')),
                )),
            ],
            'sort' => $request->string('sort', '-amount')->toString(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        try {
            Expense::create($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to create expense', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create expense.');
        }

        return back()->with('success', 'Expense created.');
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        try {
            $expense->update($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to update expense', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update expense.');
        }

        return back()->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        try {
            $expense->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete expense', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete expense.');
        }

        return back()->with('success', 'Expense deleted.');
    }
}
