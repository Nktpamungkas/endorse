<?php

namespace App\Http\Controllers;

use App\Services\KanbanColumnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KanbanColumnController extends Controller
{
    public function __construct(private readonly KanbanColumnService $service) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:50']]);
        $this->service->create(Auth::id(), $data['name']);

        return back();
    }

    public function rename(Request $request, string $slug): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:50']]);
        $this->service->rename(Auth::id(), $slug, $data['name']);

        return back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate(['slugs' => ['required', 'array'], 'slugs.*' => ['required', 'string']]);
        $this->service->reorder(Auth::id(), $data['slugs']);

        return back();
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->service->delete(Auth::id(), $slug);

        return back();
    }
}
