<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashflowRequest;
use App\Models\Pemasukan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PemasukanController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = max(5, min((int) $request->integer('per_page', 10), 100));
        $query = Pemasukan::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('tanggal')
            ->orderByDesc('updated_at');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where('deskripsi', 'like', '%'.$keyword.'%');
        }

        $editing = null;
        if ($request->filled('edit')) {
            $editing = Pemasukan::query()
                ->where('user_id', Auth::id())
                ->find($request->integer('edit'));
        }

        $items = $query->paginate($perPage)->withQueryString()
            ->through(fn (Pemasukan $item) => $this->serializeItem($item));

        return Inertia::render('Pemasukan/Index', [
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
        Pemasukan::create(array_merge(
            $request->validated(),
            [
            'user_id' => Auth::id(),
            ]
        ));

        return redirect()->route('pemasukan.index')->with('success', 'Pemasukan berhasil ditambahkan.');
    }

    public function update(CashflowRequest $request, Pemasukan $pemasukan): RedirectResponse
    {
        $this->assertOwnership($pemasukan);
        $pemasukan->update($request->validated());

        return redirect()->route('pemasukan.index')->with('success', 'Pemasukan berhasil diupdate.');
    }

    public function destroy(Pemasukan $pemasukan): RedirectResponse
    {
        $this->assertOwnership($pemasukan);
        $pemasukan->delete();

        return redirect()->route('pemasukan.index')->with('success', 'Pemasukan berhasil dihapus.');
    }

    private function serializeItem(Pemasukan $item): array
    {
        return [
            'id' => $item->id,
            'tanggal' => optional($item->tanggal)->format('Y-m-d'),
            'deskripsi' => $item->deskripsi,
            'jumlah' => (float) $item->jumlah,
            'updated_at' => optional($item->updated_at)->toIso8601String(),
        ];
    }

    private function assertOwnership(Pemasukan $pemasukan): void
    {
        if ((int) $pemasukan->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }
}
