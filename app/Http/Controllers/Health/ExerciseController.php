<?php

namespace App\Http\Controllers\Health;

use App\Data\Health\CategoryData;
use App\Data\Health\ExerciseData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Health\Exercise\StoreExerciseRequest;
use App\Http\Requests\Health\Exercise\UpdateExerciseRequest;
use App\Models\Health\Category;
use App\Models\Health\Exercise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ExerciseController extends Controller
{
    public function index(): Response
    {
        $exercises = Exercise::with('category')->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return Inertia::render('health/Exercise', [
            'exercises' => ExerciseData::collect($exercises),
            'categories' => CategoryData::collect($categories),
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

        return to_route('health.exercises.index')->with('success', 'Exercise created.');
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

        return to_route('health.exercises.index')->with('success', 'Exercise updated.');
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

        return to_route('health.exercises.index')->with('success', 'Exercise deleted.');
    }
}
