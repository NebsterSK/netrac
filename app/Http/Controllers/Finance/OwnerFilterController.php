<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\OwnerFilter\UpdateOwnerFilterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class OwnerFilterController extends Controller
{
    public function update(UpdateOwnerFilterRequest $request): RedirectResponse
    {
        try {
            $request->user()->update(['finance_owner_id' => $request->validated('owner_id')]);
        } catch (Throwable $error) {
            Log::error('Failed to update owner filter', [
                'exception_message' => $error->getMessage(),
                'exception_file' => $error->getFile(),
                'exception_line' => $error->getLine(),
                'user_id' => $request->user()->id,
            ]);

            return back()->with('error', 'Failed to update owner filter.');
        }

        return $this->backWithoutPage();
    }

    /**
     * Redirect to the previous page without its `page` query param, so a
     * paginated listing starts over on the first page of the new owner's data.
     * Without a previous URL this falls back to the home page, like back().
     */
    private function backWithoutPage(): RedirectResponse
    {
        $previousUrl = url()->previous();
        $path = explode('?', $previousUrl, 2)[0];

        parse_str((string) parse_url($previousUrl, PHP_URL_QUERY), $query);
        unset($query['page']);

        return redirect()->to($query === [] ? $path : $path.'?'.Arr::query($query));
    }
}
