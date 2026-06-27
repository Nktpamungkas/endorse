<?php

namespace App\Services;

use App\Repositories\PemasukanRepository;

class PemasukanService extends CashflowService
{
    public function __construct(PemasukanRepository $repo)
    {
        parent::__construct($repo);
    }
}
