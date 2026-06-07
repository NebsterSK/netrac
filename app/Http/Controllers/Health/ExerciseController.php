<?php

namespace App\Http\Controllers\Health;

use App\Data\Health\ExerciseCategoryData;
use App\Data\Health\ExerciseData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Health\Exercise\IndexExerciseRequest;
use App\Http\Requests\Health\Exercise\StoreExerciseRequest;
use App\Http\Requests\Health\Exercise\UpdateExerciseRequest;
use App\Models\Health\Exercise;
use App\Models\Health\ExerciseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class ExerciseController extends Controller
{
    public function index(IndexExerciseRequest $request): Response
    {
        $exercises = QueryBuilder::for(Exercise::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('exercise_category_id'),
            )
            ->allowedSorts(
                'name',
                'created_at',
                'updated_at',
                AllowedSort::callback('category', function (Builder $query, bool $descending): void {
                    $query->orderBy(
                        ExerciseCategory::query()
                            ->select('name')
                            ->whereColumn('exercise_categories.id', 'exercises.exercise_category_id'),
                        $descending ? 'desc' : 'asc',
                    );
                }),
            )
            ->defaultSort('name')
            ->with('exerciseCategory')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('health/Exercise', [
            'exercises' => ExerciseData::collect($exercises->getCollection()),
            'meta' => [
                'current_page' => $exercises->currentPage(),
                'last_page' => $exercises->lastPage(),
                'per_page' => $exercises->perPage(),
                'total' => $exercises->total(),
                'from' => $exercises->firstItem(),
                'to' => $exercises->lastItem(),
            ],
            'categories' => ExerciseCategoryData::collect(ExerciseCategory::orderBy('name')->get()),
            'filters' => [
                'name' => $request->string('filter.name')->toString(),
                'exercise_category_ids' => array_values(array_filter(
                    explode(',', (string) $request->input('filter.exercise_category_id', '')),
                )),
            ],
            'sort' => $request->string('sort', 'name')->toString(),
        ]);
    }

    public function store(StoreExerciseRequest $request): RedirectResponse
    {
        try {
            Exercise::create($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to create exercise', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create exercise.');
        }

        return back()->with('success', 'Exercise created.');
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise): RedirectResponse
    {
        try {
            $exercise->update($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to update exercise', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to update exercise.');
        }

        return back()->with('success', 'Exercise updated.');
    }

    public function destroy(Exercise $exercise): RedirectResponse
    {
        try {
            $exercise->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete exercise', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete exercise.');
        }

        return back()->with('success', 'Exercise deleted.');
    }
}
