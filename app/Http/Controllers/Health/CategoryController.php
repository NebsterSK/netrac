<?php

namespace App\Http\Controllers\Health;

use App\Data\Health\CategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Health\Category\StoreCategoryRequest;
use App\Http\Requests\Health\Category\UpdateCategoryRequest;
use App\Models\Health\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::orderBy('name')->get();

        return Inertia::render('health/Category', [
            'categories' => CategoryData::collect($categories),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        try {
            Category::create($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to create category', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create category.');
        }

        return to_route('health.categories.index')->with('success', 'Category created.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $category->update($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to update category', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update category.');
        }

        return to_route('health.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $category->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete category', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete category.');
        }

        return to_route('health.categories.index')->with('success', 'Category deleted.');
    }
}
