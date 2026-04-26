<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashflowRequest;
use App\Models\Pengeluaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PengeluaranController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = max(5, min((int) $request->integer('per_page', 10), 100));
        $query = Pengeluaran::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('tanggal')
            ->orderByDesc('updated_at');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where('deskripsi', 'like', '%'.$keyword.'%');
        }

        $editing = null;
        if ($request->filled('edit')) {
            $editing = Pengeluaran::query()
                ->where('user_id', Auth::id())
                ->find($request->integer('edit'));
        }

        $items = $query->paginate($perPage)->withQueryString()
            ->through(fn (Pengeluaran $item) => $this->serializeItem($item));

        return Inertia::render('Pengeluaran/Index', [
            'items' => $items,
            'summary' => [
                'total_items' => (clone $query)->count(),
                'total_amount' => (float) (clone $query)->sum('jumlah'),
            ],
            'filters' => [
                'q' => (string) $request->string('q'),
                'per_page' => $perPage,
            ],
            'editing' => $editing ? $this->serializeItem($editing) : null,
        ]);
    }

    public function store(CashflowRequest $request): RedirectResponse
    {
        Pengeluaran::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('pengeluaran.index')->with('success', 'Pengeluaran berhasil ditambahkan.');
    }

    public function update(CashflowRequest $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->assertOwnership($pengeluaran);
        $pengeluaran->update($request->validated());

        return redirect()->route('pengeluaran.index')->with('success', 'Pengeluaran berhasil diupdate.');
    }

    public function destroy(Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->assertOwnership($pengeluaran);
        $pengeluaran->delete();

        return redirect()->route('pengeluaran.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }

    private function serializeItem(Pengeluaran $item): array
    {
        return [
            'id' => $item->id,
            'tanggal' => optional($item->tanggal)->format('Y-m-d'),
            'deskripsi' => $item->deskripsi,
            'jumlah' => (float) $item->jumlah,
            'updated_at' => optional($item->updated_at)->toIso8601String(),
        ];
    }

    private function assertOwnership(Pengeluaran $pengeluaran): void
    {
        if ((int) $pengeluaran->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }
}
