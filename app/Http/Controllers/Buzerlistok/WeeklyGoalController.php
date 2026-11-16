<?php

namespace App\Http\Controllers\Buzerlistok;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buzerlistok\WeeklyGoal\UpdateCompletionsRequest;
use App\Models\Buzerlistok\WeeklyGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeeklyGoalController extends Controller
{
    /**
     * Set how many times this week the goal was done.
     */
    public function updateCompletions(UpdateCompletionsRequest $request, WeeklyGoal $weeklyGoal): RedirectResponse
    {
        try {
            $weeklyGoal->update([
                'completions' => $request->validated('completions'),
            ]);
        } catch (Throwable $error) {
            Log::error('Failed to update buzerlistok weekly goal completions', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'weekly_goal_id' => $weeklyGoal->id,
            ]);

            return back()->with('error', 'Failed to update weekly goal.');
        }

        return back();
    }
}
