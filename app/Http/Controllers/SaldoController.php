<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SaldoController extends Controller
{
    public function __invoke(): Response
    {
        $userId = Auth::id();

        $totalDiterima = (float) Endorsement::query()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('payment_status', 'lunas')
                    ->orWhere('status', 'selesai')
                    ->orWhereNotNull('payment_received_date');
            })
            ->sum(DB::raw('(fee_amount + reimburse_amount) - (product_cost + other_cost)'));
        $totalPemasukan = (float) Pemasukan::query()
            ->where('user_id', $userId)
            ->sum('jumlah');
        $totalPengeluaran = (float) Pengeluaran::query()
            ->where('user_id', $userId)
            ->sum('jumlah');

        return Inertia::render('Saldo', [
            'summary' => [
                'total_diterima' => $totalDiterima,
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo_akhir' => $totalDiterima + $totalPemasukan - $totalPengeluaran,
            ],
            'recentPemasukan' => Pemasukan::query()
                ->where('user_id', $userId)
                ->orderByDesc('tanggal')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Pemasukan $item) => [
                    'id' => $item->id,
                    'tanggal' => optional($item->tanggal)->format('Y-m-d'),
                    'deskripsi' => $item->deskripsi,
                    'jumlah' => (float) $item->jumlah,
                ])
                ->values(),
            'recentPengeluaran' => Pengeluaran::query()
                ->where('user_id', $userId)
                ->orderByDesc('tanggal')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Pengeluaran $item) => [
                    'id' => $item->id,
                    'tanggal' => optional($item->tanggal)->format('Y-m-d'),
                    'deskripsi' => $item->deskripsi,
                    'jumlah' => (float) $item->jumlah,
                ])
                ->values(),
        ]);
    }
}
