<?php

namespace App\Services;

use App\Repositories\PengeluaranRepository;

class PengeluaranService extends CashflowService
{
    public function __construct(PengeluaranRepository $repo)
    {
        parent::__construct($repo);
    }
}
