<?php

namespace App\Http\Controllers;

use App\Services\NeracaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NeracaController extends Controller
{
    public function __construct(private readonly NeracaService $service) {}

    public function __invoke(Request $request): Response
    {
        $bulan = $request->integer('bulan', Carbon::now()->month);
        $tahun = $request->integer('tahun', Carbon::now()->year);

        return Inertia::render('Neraca', $this->service->data(Auth::id(), $bulan, $tahun));
    }
}
