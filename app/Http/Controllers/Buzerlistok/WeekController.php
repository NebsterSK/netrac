<?php

namespace App\Http\Controllers\Buzerlistok;

use App\Data\Buzerlistok\StoreWeekData;
use App\Data\Buzerlistok\UpdateWeekData;
use App\Data\Buzerlistok\WeekData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Buzerlistok\Week\StoreWeekRequest;
use App\Http\Requests\Buzerlistok\Week\UpdateWeekRequest;
use App\Models\Buzerlistok\Mark;
use App\Models\Buzerlistok\Week;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
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
            Log::error('Failed to create buzerlistok week', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
            ]);

            return back()->with('error', 'Failed to create buzerlistok.');
        }

        return back()->with('success', 'Buzerlistok created.');
    }

    public function edit(Week $week): Response
    {
        $week->load('goals.marks');

        return Inertia::render('buzerlistok/Edit', [
            'week' => WeekData::fromWeek($week),
        ]);
    }

    /**
     * Moving the week to another Monday shifts its marks along by the same
     * number of days, so they stay on the same weekday.
     */
    public function update(UpdateWeekRequest $request, Week $week): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $week): void {
                $data = UpdateWeekData::from($request->validated());

                $shiftDays = (int) $week->starts_on->diffInDays(Carbon::parse($data->starts_on));

                $week->update([
                    'starts_on' => $data->starts_on,
                ]);

                $keptGoalIds = collect($data->goals)
                    ->pluck('id')
                    ->filter()
                    ->all();

                $week->goals()->whereNotIn('id', $keptGoalIds)->delete();

                foreach ($data->goals as $position => $goal) {
                    $attributes = [
                        'name' => $goal['name'],
                        'position' => $position,
                    ];

                    if (isset($goal['id'])) {
                        $week->goals()->whereKey($goal['id'])->update($attributes);
                    } else {
                        $week->goals()->create($attributes);
                    }
                }

                if ($shiftDays !== 0) {
                    Mark::whereIn('goal_id', $keptGoalIds)
                        ->get()
                        ->each(fn (Mark $mark) => $mark->update([
                            'marked_on' => $mark->marked_on->copy()->addDays($shiftDays),
                        ]));
                }
            });
        } catch (Throwable $error) {
            Log::error('Failed to update buzerlistok week', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'week_id' => $week->id,
            ]);

            return back()->with('error', 'Failed to update buzerlistok.');
        }

        return to_route('buzerlistok.index')->with('success', 'Buzerlistok updated.');
    }

    public function destroy(Week $week): RedirectResponse
    {
        try {
            $week->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete buzerlistok week', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'week_id' => $week->id,
            ]);

            return back()->with('error', 'Failed to delete buzerlistok.');
        }

        return back()->with('success', 'Buzerlistok deleted.');
    }
}
