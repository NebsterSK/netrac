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
            // A new category forms its own lowest tier so the bottom-most tier
            // keeps exactly one category (the one randomize places last).
            ExerciseCategory::create([
                ...$request->validated(),
                'priority' => (ExerciseCategory::max('priority') ?? 0) + 1,
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
        $priorities = ExerciseCategory::query()
            ->where('id', '!=', $exerciseCategory->id)
            ->pluck('priority')
            ->push((int) $request->validated('priority'))
            ->all();

        if (! $this->lowestTierStaysSingle($priorities)) {
            return back()->with('error', 'The lowest priority tier must keep exactly one category.');
        }

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
        $remaining = ExerciseCategory::query()
            ->where('id', '!=', $exerciseCategory->id)
            ->pluck('priority')
            ->all();

        if (! $this->lowestTierStaysSingle($remaining)) {
            return back()->with('error', 'The lowest priority tier must keep exactly one category. Move a category down before deleting this one.');
        }

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

    /**
     * The lowest priority tier (highest priority number) must hold exactly one
     * category. An empty set is allowed (no tiers to constrain).
     *
     * @param  array<int, int>  $priorities
     */
    private function lowestTierStaysSingle(array $priorities): bool
    {
        if ($priorities === []) {
            return true;
        }

        $counts = array_count_values($priorities);

        return $counts[max(array_keys($counts))] === 1;
    }
}
