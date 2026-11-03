<?php

namespace App\Http\Controllers\Health;

use App\Data\Health\WeightData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Health\Weight\StoreWeightRequest;
use App\Http\Requests\Health\Weight\UpdateWeightRequest;
use App\Models\Health\Weight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class WeightController extends Controller
{
    public function index(): Response
    {
        $weights = Weight::orderByDesc('date')->get();

        return Inertia::render('health/Weight', [
            'weights' => $weights->map(fn (Weight $weight): WeightData => WeightData::fromWeight($weight)),
        ]);
    }

    public function store(StoreWeightRequest $request): RedirectResponse
    {
        try {
            Weight::create($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to create weight', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'user_id' => $request->user()?->id,
            ]);

            return back()->with('error', 'Failed to add weight.');
        }

        return back()->with('success', 'Weight added.');
    }

    public function update(UpdateWeightRequest $request, Weight $weight): RedirectResponse
    {
        try {
            $weight->update($request->validated());
        } catch (Throwable $error) {
            Log::error('Failed to update weight', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'user_id' => $request->user()?->id,
            ]);

            return back()->with('error', 'Failed to update weight.');
        }

        return back()->with('success', 'Weight updated.');
    }

    public function destroy(Weight $weight): RedirectResponse
    {
        try {
            $weight->delete();
        } catch (Throwable $error) {
            Log::error('Failed to delete weight', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Failed to delete weight.');
        }

        return back()->with('success', 'Weight deleted.');
    }
}
