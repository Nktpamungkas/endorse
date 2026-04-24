<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $selectedStatus = request()->query('status_view', 'deal_masuk');
        if (! array_key_exists($selectedStatus, Endorsement::STATUS_OPTIONS)) {
            $selectedStatus = 'deal_masuk';
        }

        $statusCounts = Endorsement::query()
            ->where('user_id', Auth::id())
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalIncome = (float) Endorsement::where('user_id', Auth::id())->sum(DB::raw('fee_amount + reimburse_amount'));
        $totalCost = (float) Endorsement::where('user_id', Auth::id())->sum(DB::raw('product_cost + other_cost'));
        $receivedNetProfit = (float) Endorsement::where('user_id', Auth::id())
            ->where('status', 'selesai')
            ->sum(DB::raw('(fee_amount + reimburse_amount) - (product_cost + other_cost)'));
        $waitingPayment = (int) ($statusCounts['menunggu_payment'] ?? 0);
        $waitingPaymentItems = Endorsement::query()
            ->where('user_id', Auth::id())
            ->where('status', 'menunggu_payment')
            ->orderByRaw("CASE WHEN payment_due_date IS NULL THEN 1 ELSE 0 END, payment_due_date ASC, updated_at DESC")
            ->limit(10)
            ->get()
            ->map(fn (Endorsement $endorsement) => $this->serializeDashboardItem($endorsement));

        $selectedStatusItems = Endorsement::query()
            ->where('user_id', Auth::id())
            ->where('status', $selectedStatus)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn (Endorsement $endorsement) => $this->serializeDashboardItem($endorsement));

        $rawMonthlyStats = Endorsement::query()
            ->where('user_id', Auth::id())
            ->selectRaw("FORMAT(created_at, 'yyyy-MM-01') as month_key")
            ->selectRaw('SUM(fee_amount + reimburse_amount) as income')
            ->selectRaw('SUM(product_cost + other_cost) as cost')
            ->groupByRaw("FORMAT(created_at, 'yyyy-MM-01')")
            ->orderByRaw("FORMAT(created_at, 'yyyy-MM-01')")
            ->get()
            ->keyBy('month_key');

        $monthlyStats = collect(range(5, 0))->map(function (int $offset) use ($rawMonthlyStats) {
            $monthKey = Carbon::now()->startOfMonth()->subMonths($offset)->format('Y-m-01');
            $row = $rawMonthlyStats->get($monthKey);

            return [
                'month_key' => $monthKey,
                'income' => (float) ($row->income ?? 0),
                'cost' => (float) ($row->cost ?? 0),
            ];
        })->values();

        return Inertia::render('Dashboard', [
            'statusCounts' => $statusCounts,
            'totalIncome' => $totalIncome,
            'totalCost' => $totalCost,
            'netProfit' => $totalIncome - $totalCost,
            'receivedNetProfit' => $receivedNetProfit,
            'waitingPayment' => $waitingPayment,
            'waitingPaymentItems' => $waitingPaymentItems,
            'selectedStatus' => $selectedStatus,
            'selectedStatusItems' => $selectedStatusItems,
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
            'paymentStatusOptions' => Endorsement::PAYMENT_STATUS_OPTIONS,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    private function serializeDashboardItem(Endorsement $endorsement): array
    {
        return [
            'id' => $endorsement->id,
            'brand_name' => $endorsement->brand_name,
            'campaign_name' => $endorsement->campaign_name,
            'platform' => $endorsement->platform,
            'status' => $endorsement->status,
            'posting_date' => optional($endorsement->posting_date)->format('Y-m-d'),
            'insight_due_at' => optional($endorsement->insight_due_at)->format('Y-m-d'),
            'insight_sent_at' => optional($endorsement->insight_sent_at)->format('Y-m-d'),
            'payment_status' => $endorsement->payment_status,
            'payment_due_date' => optional($endorsement->payment_due_date)->format('Y-m-d'),
            'total_cost' => (float) $endorsement->total_cost,
            'net_profit' => (float) $endorsement->net_profit,
        ];
    }
}
