<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashflowRequest;
use App\Models\Pemasukan;
use App\Services\PemasukanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PemasukanController extends Controller
{
    public function __construct(private readonly PemasukanService $service) {}

    public function index(Request $request): Response
    {
        $perPage = max(5, min((int) $request->integer('per_page', 10), 100));

        return Inertia::render('Pemasukan/Index', $this->service->indexData(
            Auth::id(),
            (string) $request->string('q'),
            $perPage,
            $request->filled('edit') ? $request->integer('edit') : null,
        ));
    }

    public function store(CashflowRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), Auth::id());

        return redirect()->route('pemasukan.index')->with('success', 'Pemasukan berhasil ditambahkan.');
    }

    public function update(CashflowRequest $request, Pemasukan $pemasukan): RedirectResponse
    {
        $this->authorizeOwnership($pemasukan);
        $this->service->update($pemasukan, $request->validated());

        return redirect()->route('pemasukan.index')->with('success', 'Pemasukan berhasil diupdate.');
    }

    public function destroy(Pemasukan $pemasukan): RedirectResponse
    {
        $this->authorizeOwnership($pemasukan);
        $this->service->delete($pemasukan);

        return redirect()->route('pemasukan.index')->with('success', 'Pemasukan berhasil dihapus.');
    }

    private function authorizeOwnership(Pemasukan $pemasukan): void
    {
        abort_if((int) $pemasukan->user_id !== (int) Auth::id(), 403);
    }
}
