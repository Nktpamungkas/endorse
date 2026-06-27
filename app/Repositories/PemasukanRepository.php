<?php

namespace App\Repositories;

use App\Models\Pemasukan;

class PemasukanRepository extends CashflowRepository
{
    protected function model(): string
    {
        return Pemasukan::class;
    }
}
