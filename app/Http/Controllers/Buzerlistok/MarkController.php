<?php

namespace App\Http\Controllers\Buzerlistok;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buzerlistok\Mark\UpdateMarkRequest;
use App\Models\Buzerlistok\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarkController extends Controller
{
    /**
     * Paint a single day × goal cell. A null status erases the mark.
     */
    public function update(UpdateMarkRequest $request, Goal $goal): RedirectResponse
    {
        try {
            $markedOn = $request->validated('marked_on');
            $status = $request->validated('status');

            if ($status === null) {
                $goal->marks()->where('marked_on', $markedOn)->delete();

                return back();
            }

            $goal->marks()->updateOrCreate(
                ['marked_on' => $markedOn],
                [
                    'status' => $status,
                    'comment' => $request->validated('comment'),
                ],
            );
        } catch (Throwable $error) {
            Log::error('Failed to update buzerlistok mark', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'goal_id' => $goal->id,
            ]);

            return back()->with('error', 'Failed to update mark.');
        }

        return back();
    }
}
