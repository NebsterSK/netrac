<?php

namespace App\Http\Controllers\Health;

use App\Data\Health\ExerciseData;
use App\Data\Health\SessionDetailData;
use App\Data\Health\SessionListItemData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Health\Session\StoreSessionRequest;
use App\Http\Requests\Health\Session\UpdateSessionExerciseRequest;
use App\Models\Health\Exercise;
use App\Models\Health\WorkoutSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SessionController extends Controller
{
    public function index(): Response
    {
        $sessions = WorkoutSession::with('exerciseEntries')
            ->orderBy('performed_at', 'desc')
            ->get();

        return Inertia::render('health/sessions/Index', [
            'sessions' => $sessions->map(
                fn (WorkoutSession $session): SessionListItemData => SessionListItemData::fromSession($session),
            ),
        ]);
    }

    public function create(): Response
    {
        $exercises = Exercise::with('exerciseCategory')->orderBy('name')->get();

        return Inertia::render('health/sessions/Create', [
            'exercises' => ExerciseData::collect($exercises),
        ]);
    }

    public function store(StoreSessionRequest $request): RedirectResponse
    {
        try {
            $session = DB::transaction(function () use ($request): WorkoutSession {
                $session = WorkoutSession::create([
                    'performed_at' => now(),
                ]);

                $pivot = [];
                foreach ($request->validated('exercise_ids') as $position => $exerciseId) {
                    $pivot[$exerciseId] = ['position' => $position, 'completed' => false];
                }

                $session->exercises()->attach($pivot);

                return $session;
            });
        } catch (Throwable $error) {
            Log::error('Failed to create session', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create session.');
        }

        return to_route('health.sessions.show', $session)->with('success', 'Session created.');
    }

    public function show(WorkoutSession $session): Response
    {
        $session->load('exerciseEntries.exercise.exerciseCategory');

        return Inertia::render('health/sessions/Show', [
            'session' => SessionDetailData::fromSession($session),
        ]);
    }

    public function updateExercise(UpdateSessionExerciseRequest $request, WorkoutSession $session, Exercise $exercise): RedirectResponse
    {
        try {
            $session->exercises()->updateExistingPivot($exercise->id, [
                'completed' => $request->boolean('completed'),
            ]);
        } catch (Throwable $error) {
            Log::error('Failed to update session exercise', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'session_id' => $session->id,
                'exercise_id' => $exercise->id,
            ]);

            return back()->with('error', 'Failed to update completion.');
        }

        return back();
    }

    public function destroy(WorkoutSession $session): RedirectResponse
    {
        try {
            $session->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete session', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to delete session.');
        }

        return to_route('health.sessions.index')->with('success', 'Session deleted.');
    }
}
