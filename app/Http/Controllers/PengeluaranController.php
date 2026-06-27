<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashflowRequest;
use App\Models\Pengeluaran;
use App\Services\PengeluaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PengeluaranController extends Controller
{
    public function __construct(private readonly PengeluaranService $service) {}

    public function index(Request $request): Response
    {
        $perPage = max(5, min((int) $request->integer('per_page', 10), 100));

        return Inertia::render('Pengeluaran/Index', $this->service->indexData(
            Auth::id(),
            (string) $request->string('q'),
            $perPage,
            $request->filled('edit') ? $request->integer('edit') : null,
        ));
    }

    public function store(CashflowRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), Auth::id());

        return redirect()->route('pengeluaran.index')->with('success', 'Pengeluaran berhasil ditambahkan.');
    }

    public function update(CashflowRequest $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->authorizeOwnership($pengeluaran);
        $this->service->update($pengeluaran, $request->validated());

        return redirect()->route('pengeluaran.index')->with('success', 'Pengeluaran berhasil diupdate.');
    }

    public function destroy(Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->authorizeOwnership($pengeluaran);
        $this->service->delete($pengeluaran);

        return redirect()->route('pengeluaran.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }

    private function authorizeOwnership(Pengeluaran $pengeluaran): void
    {
        abort_if((int) $pengeluaran->user_id !== (int) Auth::id(), 403);
    }
}
