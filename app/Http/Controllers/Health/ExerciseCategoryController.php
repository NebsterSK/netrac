<?php

namespace App\Http\Controllers\Health;

use App\Data\Health\ExerciseCategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Health\ExerciseCategory\StoreExerciseCategoryRequest;
use App\Http\Requests\Health\ExerciseCategory\UpdateExerciseCategoryPriorityRequest;
use App\Http\Requests\Health\ExerciseCategory\UpdateExerciseCategoryRequest;
use App\Models\Health\ExerciseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ExerciseCategoryController extends Controller
{
    public function index(): Response
    {
        $exerciseCategories = ExerciseCategory::orderBy('priority')->orderBy('name')->get();

        return Inertia::render('health/ExerciseCategory', [
            'categories' => ExerciseCategoryData::collect($exerciseCategories),
        ]);
    }

    public function store(StoreExerciseCategoryRequest $request): RedirectResponse
    {
        try {
            ExerciseCategory::create([
                ...$request->validated(),
                'priority' => ExerciseCategory::max('priority') ?? 1,
            ]);
        } catch (Throwable $error) {
            Log::error('Failed to create exercise category', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create category.');
        }

        return to_route('health.exercise-categories.index')->with('success', 'Category created.');
    }

    public function update(UpdateExerciseCategoryRequest $request, ExerciseCategory $exerciseCategory): RedirectResponse
    {
        try {
            $exerciseCategory->update($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to update exercise category', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update category.');
        }

        return to_route('health.exercise-categories.index')->with('success', 'Category updated.');
    }

    public function updatePriority(UpdateExerciseCategoryPriorityRequest $request, ExerciseCategory $exerciseCategory): RedirectResponse
    {
        try {
            $exerciseCategory->update($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to update exercise category priority', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update priority.');
        }

        return back();
    }

    public function destroy(ExerciseCategory $exerciseCategory): RedirectResponse
    {
        try {
            $exerciseCategory->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete exercise category', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete category.');
        }

        return to_route('health.exercise-categories.index')->with('success', 'Category deleted.');
    }
}
