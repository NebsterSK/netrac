<?php

namespace App\Http\Controllers;

use App\Http\Requests\Statement\StoreStatementRequest;
use App\Http\Requests\Statement\UpdateStatementRequest;
use App\Models\Statement;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NetWorthController extends Controller
{
    public function index(): Response
    {
        $statements = Statement::orderBy('date', 'desc')->get();

        return Inertia::render('NetWorth', [
            'statements' => $statements,
            'existingDates' => $statements->pluck('date')->toArray(),
        ]);
    }

    public function store(StoreStatementRequest $request): RedirectResponse
    {
        Statement::create($request->validated());

        return to_route('net-worth.index');
    }

    public function update(UpdateStatementRequest $request, Statement $statement): RedirectResponse
    {
        $statement->update($request->validated());

        return to_route('net-worth.index');
    }

    public function destroy(Statement $statement): RedirectResponse
    {
        $statement->delete();

        return to_route('net-worth.index');
    }
}
