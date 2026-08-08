<?php

namespace App\Http\Controllers\Buzerlistok;

use App\Data\Buzerlistok\StoreWeekData;
use App\Data\Buzerlistok\WeekData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Buzerlistok\Week\StoreWeekRequest;
use App\Models\Buzerlistok\Week;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WeekController extends Controller
{
    public function index(): Response
    {
        $weeks = Week::with('goals.marks')
            ->orderBy('starts_on', 'desc')
            ->limit(12)
            ->get();

        return Inertia::render('buzerlistok/Index', [
            'weeks' => $weeks->map(
                fn (Week $week): WeekData => WeekData::fromWeek($week),
            ),
        ]);
    }

    public function store(StoreWeekRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $data = StoreWeekData::from($request->validated());

                $week = Week::create([
                    'starts_on' => $data->starts_on,
                ]);

                foreach ($data->goals as $position => $name) {
                    $week->goals()->create([
                        'name' => $name,
                        'position' => $position,
                    ]);
                }
            });
        } catch (Throwable $error) {
            Log::error('Failed to create buzerlístek week', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create buzerlístek.');
        }

        return back()->with('success', 'Buzerlístek created.');
    }

    public function destroy(Week $week): RedirectResponse
    {
        try {
            $week->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete buzerlístek week', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'week_id' => $week->id,
            ]);

            return back()->with('error', 'Failed to delete buzerlístek.');
        }

        return back()->with('success', 'Buzerlístek deleted.');
    }
}
