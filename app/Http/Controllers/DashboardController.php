<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $selectedStatus = request()->query('status_view', 'deal_masuk');
        if (! array_key_exists($selectedStatus, Endorsement::STATUS_OPTIONS)) {
            $selectedStatus = 'deal_masuk';
        }

        $statusCounts = Endorsement::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalIncome = (float) Endorsement::query()->sum(DB::raw('fee_amount + reimburse_amount'));
        $totalCost = (float) Endorsement::query()->sum(DB::raw('product_cost + other_cost'));
        $waitingPayment = Endorsement::query()->where('payment_status', '!=', 'lunas')->count();

        $selectedStatusItems = Endorsement::query()
            ->where('status', $selectedStatus)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'statusCounts' => $statusCounts,
            'totalIncome' => $totalIncome,
            'totalCost' => $totalCost,
            'netProfit' => $totalIncome - $totalCost,
            'waitingPayment' => $waitingPayment,
            'selectedStatus' => $selectedStatus,
            'selectedStatusItems' => $selectedStatusItems,
            'statusOptions' => Endorsement::STATUS_OPTIONS,
        ]);
    }
}
