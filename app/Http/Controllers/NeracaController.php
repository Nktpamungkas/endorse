<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NeracaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $userId = Auth::id();
        $bulan = $request->integer('bulan', 0);
        $tahun = $request->integer('tahun', Carbon::now()->year);

        $startDate = $bulan > 0
            ? Carbon::create($tahun, $bulan, 1)->startOfMonth()
            : Carbon::create($tahun, 1, 1)->startOfYear();

        // Saldo pembuka: semua transaksi sebelum periode filter
        $saldoPembuka = 0.0;
        $saldoPembuka += (float) Endorsement::query()
            ->where('user_id', $userId)
            ->where('created_at', '<', $startDate)
            ->sum(DB::raw('(fee_amount + reimburse_amount) - (product_cost + other_cost)'));
        $saldoPembuka += (float) Pemasukan::query()
            ->where('user_id', $userId)
            ->where('tanggal', '<', $startDate)
            ->sum('jumlah');
        $saldoPembuka -= (float) Pengeluaran::query()
            ->where('user_id', $userId)
            ->where('tanggal', '<', $startDate)
            ->sum('jumlah');

        // Transaksi dalam periode
        $endorsements = Endorsement::query()
            ->where('user_id', $userId)
            ->when($bulan > 0, fn ($q) => $q->whereYear('created_at', $tahun)->whereMonth('created_at', $bulan))
            ->when($bulan === 0, fn ($q) => $q->whereYear('created_at', $tahun))
            ->get()
            ->map(fn (Endorsement $e) => [
                'tanggal' => Carbon::parse($e->created_at)->format('Y-m-d'),
                'keterangan' => trim($e->brand_name.($e->campaign_name ? ' — '.$e->campaign_name : '')),
                'tipe' => 'endorsement',
                'debit' => (float) ($e->fee_amount + $e->reimburse_amount),
                'kredit' => (float) ($e->product_cost + $e->other_cost),
                'ref_id' => $e->id,
            ]);

        $pemasukan = Pemasukan::query()
            ->where('user_id', $userId)
            ->when($bulan > 0, fn ($q) => $q->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan))
            ->when($bulan === 0, fn ($q) => $q->whereYear('tanggal', $tahun))
            ->get()
            ->map(fn (Pemasukan $p) => [
                'tanggal' => optional($p->tanggal)->format('Y-m-d') ?? Carbon::parse($p->created_at)->format('Y-m-d'),
                'keterangan' => $p->deskripsi,
                'tipe' => 'pemasukan',
                'debit' => (float) $p->jumlah,
                'kredit' => 0.0,
                'ref_id' => $p->id,
            ]);

        $pengeluaran = Pengeluaran::query()
            ->where('user_id', $userId)
            ->when($bulan > 0, fn ($q) => $q->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan))
            ->when($bulan === 0, fn ($q) => $q->whereYear('tanggal', $tahun))
            ->get()
            ->map(fn (Pengeluaran $p) => [
                'tanggal' => optional($p->tanggal)->format('Y-m-d') ?? Carbon::parse($p->created_at)->format('Y-m-d'),
                'keterangan' => $p->deskripsi,
                'tipe' => 'pengeluaran',
                'debit' => 0.0,
                'kredit' => (float) $p->jumlah,
                'ref_id' => $p->id,
            ]);

        $rows = $endorsements->merge($pemasukan)->merge($pengeluaran)
            ->sortBy('tanggal')
            ->values();

        // Hitung running saldo mulai dari saldo pembuka
        $saldo = round($saldoPembuka, 2);
        $rows = $rows->map(function (array $row) use (&$saldo): array {
            $saldo += $row['debit'] - $row['kredit'];
            $row['saldo'] = round($saldo, 2);

            return $row;
        });

        $totalDebit = round($rows->sum('debit'), 2);
        $totalKredit = round($rows->sum('kredit'), 2);

        return Inertia::render('Neraca', [
            'rows' => $rows->values(),
            'saldoPembuka' => round($saldoPembuka, 2),
            'summary' => [
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'saldo_akhir' => round($saldoPembuka + $totalDebit - $totalKredit, 2),
            ],
            'filters' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
            ],
        ]);
    }
}
