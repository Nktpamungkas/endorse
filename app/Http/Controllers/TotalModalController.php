<?php

namespace App\Http\Controllers;

use App\Services\TotalModalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TotalModalController extends Controller
{
    public function __construct(private readonly TotalModalService $service) {}

    public function __invoke(Request $request): Response
    {
        $perPage = max(5, min((int) $request->integer('per_page', 10), 100));
        $sort = (string) $request->string('sort', 'highest_modal');
        if (! in_array($sort, TotalModalService::SORTS, true)) {
            $sort = 'highest_modal';
        }

        $filters = [
            'q' => (string) $request->string('q'),
            'status' => (string) $request->string('status'),
            'platform' => (string) $request->string('platform'),
        ];

        return Inertia::render('TotalModal', $this->service->data(Auth::id(), $filters, $sort, $perPage));
    }
}
