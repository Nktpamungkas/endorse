<?php

namespace App\Services;

use App\Models\Endorsement;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Repositories\EndorsementRepository;
use App\Repositories\PemasukanRepository;
use App\Repositories\PengeluaranRepository;
use Illuminate\Support\Carbon;

class NeracaService
{
    public function __construct(
        private readonly EndorsementRepository $endorsements,
        private readonly PemasukanRepository $pemasukan,
        private readonly PengeluaranRepository $pengeluaran,
    ) {}

    public function data(int $userId, int $bulan, int $tahun): array
    {
        $saldoPembuka = $this->saldoPembuka($userId, $bulan, $tahun);

        $endorsementRows = $this->endorsements->paidInPeriod($userId, $bulan, $tahun)
            ->toBase()->map(fn (Endorsement $e) => [
                'tanggal' => Carbon::parse($e->created_at)->format('Y-m-d'),
                'keterangan' => trim($e->brand_name.($e->campaign_name ? ' — '.$e->campaign_name : '')),
                'tipe' => 'endorsement',
                'debit' => (float) ($e->fee_amount + $e->reimburse_amount),
                'kredit' => (float) ($e->product_cost + $e->other_cost),
                'ref_id' => $e->id,
            ]);

        $pemasukanRows = $this->pemasukan->inPeriod($userId, $bulan, $tahun)
            ->toBase()->map(fn (Pemasukan $p) => [
                'tanggal' => optional($p->tanggal)->format('Y-m-d') ?? Carbon::parse($p->created_at)->format('Y-m-d'),
                'keterangan' => $p->deskripsi,
                'tipe' => 'pemasukan',
                'debit' => (float) $p->jumlah,
                'kredit' => 0.0,
                'ref_id' => $p->id,
            ]);

        $pengeluaranRows = $this->pengeluaran->inPeriod($userId, $bulan, $tahun)
            ->toBase()->map(fn (Pengeluaran $p) => [
                'tanggal' => optional($p->tanggal)->format('Y-m-d') ?? Carbon::parse($p->created_at)->format('Y-m-d'),
                'keterangan' => $p->deskripsi,
                'tipe' => 'pengeluaran',
                'debit' => 0.0,
                'kredit' => (float) $p->jumlah,
                'ref_id' => $p->id,
            ]);

        $rows = $endorsementRows->merge($pemasukanRows)->merge($pengeluaranRows)
            ->sortBy('tanggal')->values();

        $saldo = round($saldoPembuka, 2);
        $rows = $rows->map(function (array $row) use (&$saldo): array {
            $saldo += $row['debit'] - $row['kredit'];
            $row['saldo'] = round($saldo, 2);

            return $row;
        });

        $totalDebit = round($rows->sum('debit'), 2);
        $totalKredit = round($rows->sum('kredit'), 2);

        return [
            'rows' => $rows->values(),
            'saldoPembuka' => round($saldoPembuka, 2),
            'summary' => [
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'saldo_akhir' => round($saldoPembuka + $totalDebit - $totalKredit, 2),
            ],
            'filters' => ['bulan' => $bulan, 'tahun' => $tahun],
        ];
    }

    private function saldoPembuka(int $userId, int $bulan, int $tahun): float
    {
        if ($tahun === 0) {
            return 0.0;
        }

        $startDate = $bulan > 0
            ? Carbon::create($tahun, $bulan, 1)->startOfMonth()
            : Carbon::create($tahun, 1, 1)->startOfYear();

        return $this->endorsements->paidNetProfitBefore($userId, $startDate)
            + $this->pemasukan->sumBefore($userId, $startDate)
            - $this->pengeluaran->sumBefore($userId, $startDate);
    }
}
