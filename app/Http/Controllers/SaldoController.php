<?php

namespace App\Http\Controllers;

use App\Services\SaldoService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SaldoController extends Controller
{
    public function __construct(private readonly SaldoService $service) {}

    public function __invoke(): Response
    {
        return Inertia::render('Saldo', $this->service->data(Auth::id()));
    }
}
