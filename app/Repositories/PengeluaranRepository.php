<?php

namespace App\Repositories;

use App\Models\Pengeluaran;

class PengeluaranRepository extends CashflowRepository
{
    protected function model(): string
    {
        return Pengeluaran::class;
    }
}
