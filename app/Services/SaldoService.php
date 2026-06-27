<?php

namespace App\Services;

use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Repositories\EndorsementRepository;
use App\Repositories\PemasukanRepository;
use App\Repositories\PengeluaranRepository;

class SaldoService
{
    public function __construct(
        private readonly EndorsementRepository $endorsements,
        private readonly PemasukanRepository $pemasukan,
        private readonly PengeluaranRepository $pengeluaran,
    ) {}

    public function data(int $userId): array
    {
        $totalDiterima = $this->endorsements->paidNetProfit($userId);
        $totalPemasukan = $this->pemasukan->sumAll($userId);
        $totalPengeluaran = $this->pengeluaran->sumAll($userId);

        return [
            'summary' => [
                'total_diterima' => $totalDiterima,
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo_akhir' => $totalDiterima + $totalPemasukan - $totalPengeluaran,
            ],
            'recentPemasukan' => $this->pemasukan->recent($userId)
                ->map(fn (Pemasukan $item) => $this->serializeCashflow($item))->values(),
            'recentPengeluaran' => $this->pengeluaran->recent($userId)
                ->map(fn (Pengeluaran $item) => $this->serializeCashflow($item))->values(),
        ];
    }

    private function serializeCashflow($item): array
    {
        return [
            'id' => $item->id,
            'tanggal' => optional($item->tanggal)->format('Y-m-d'),
            'deskripsi' => $item->deskripsi,
            'jumlah' => (float) $item->jumlah,
        ];
    }
}
