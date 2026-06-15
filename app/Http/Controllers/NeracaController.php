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
        $tahun = $request->integer('tahun', 0); // 0 = semua tahun

        // Hitung saldo pembuka (transaksi sebelum periode filter)
        $saldoPembuka = $this->hitungSaldoPembuka($userId, $bulan, $tahun);

        // Query endorsement — hanya yang sudah dibayar (sama dengan Saldo)
        $endorsementQuery = Endorsement::query()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('payment_status', 'lunas')
                    ->orWhere('status', 'selesai')
                    ->orWhereNotNull('payment_received_date');
            });
        $this->applyPeriodFilter($endorsementQuery, 'created_at', $bulan, $tahun);

        $endorsements = $endorsementQuery->get()->map(fn (Endorsement $e) => [
            'tanggal' => Carbon::parse($e->created_at)->format('Y-m-d'),
            'keterangan' => trim($e->brand_name.($e->campaign_name ? ' — '.$e->campaign_name : '')),
            'tipe' => 'endorsement',
            'debit' => (float) ($e->fee_amount + $e->reimburse_amount),
            'kredit' => (float) ($e->product_cost + $e->other_cost),
            'ref_id' => $e->id,
        ]);

        $pemasukanQuery = Pemasukan::query()->where('user_id', $userId);
        $this->applyPeriodFilter($pemasukanQuery, 'tanggal', $bulan, $tahun);

        $pemasukan = $pemasukanQuery->get()->map(fn (Pemasukan $p) => [
            'tanggal' => optional($p->tanggal)->format('Y-m-d') ?? Carbon::parse($p->created_at)->format('Y-m-d'),
            'keterangan' => $p->deskripsi,
            'tipe' => 'pemasukan',
            'debit' => (float) $p->jumlah,
            'kredit' => 0.0,
            'ref_id' => $p->id,
        ]);

        $pengeluaranQuery = Pengeluaran::query()->where('user_id', $userId);
        $this->applyPeriodFilter($pengeluaranQuery, 'tanggal', $bulan, $tahun);

        $pengeluaran = $pengeluaranQuery->get()->map(fn (Pengeluaran $p) => [
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

    private function hitungSaldoPembuka(int $userId, int $bulan, int $tahun): float
    {
        if ($tahun === 0) {
            return 0.0; // Semua tahun = tidak ada saldo pembuka
        }

        $startDate = $bulan > 0
            ? Carbon::create($tahun, $bulan, 1)->startOfMonth()
            : Carbon::create($tahun, 1, 1)->startOfYear();

        $saldo = 0.0;
        $saldo += (float) Endorsement::query()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('payment_status', 'lunas')
                    ->orWhere('status', 'selesai')
                    ->orWhereNotNull('payment_received_date');
            })
            ->where('created_at', '<', $startDate)
            ->sum(DB::raw('(fee_amount + reimburse_amount) - (product_cost + other_cost)'));
        $saldo += (float) Pemasukan::query()
            ->where('user_id', $userId)
            ->where('tanggal', '<', $startDate)
            ->sum('jumlah');
        $saldo -= (float) Pengeluaran::query()
            ->where('user_id', $userId)
            ->where('tanggal', '<', $startDate)
            ->sum('jumlah');

        return $saldo;
    }

    private function applyPeriodFilter($query, string $column, int $bulan, int $tahun): void
    {
        if ($tahun === 0) {
            return; // Semua tahun, tidak ada filter
        }

        if ($bulan > 0) {
            $query->whereYear($column, $tahun)->whereMonth($column, $bulan);
        } else {
            $query->whereYear($column, $tahun);
        }
    }
}
