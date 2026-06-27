<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function __invoke(Request $request): Response
    {
        $selectedStatus = $request->query('status_view', 'deal_masuk');
        if (! array_key_exists($selectedStatus, Endorsement::STATUS_OPTIONS)) {
            $selectedStatus = 'deal_masuk';
        }

        $statusPerPage = max(10, min((int) $request->integer('status_per_page', 10), 100));

        return Inertia::render('Dashboard', $this->service->data(
            Auth::id(),
            $selectedStatus,
            (string) $request->string('status_search'),
            $statusPerPage,
        ));
    }
}
