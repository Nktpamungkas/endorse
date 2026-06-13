<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $userId = Auth::id();
        $selectedStatus = $request->query('status_view', 'deal_masuk');
        if (! array_key_exists($selectedStatus, Endorsement::STATUS_OPTIONS)) {
            $selectedStatus = 'deal_masuk';
        }
        $statusSearch = (string) $request->string('status_search');
        $statusPerPage = max(10, min((int) $request->integer('status_per_page', 10), 100));

        $statusCounts = Endorsement::query()
            ->where('user_id', $userId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalIncome = (float) Endorsement::where('user_id', $userId)->sum(DB::raw('fee_amount + reimburse_amount'));
        $totalCost = (float) Endorsement::where('user_id', $userId)->sum(DB::raw('product_cost + other_cost'));
        $receivedNetProfit = (float) Endorsement::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('payment_status', 'lunas')
                    ->orWhere('status', 'selesai')
                    ->orWhereNotNull('payment_received_date');
            })
            ->sum(DB::raw('(fee_amount + reimburse_amount) - (product_cost + other_cost)'));
        $waitingPaymentItemsQuery = Endorsement::query()
            ->where('user_id', $userId)
            ->where('status', 'menunggu_payment')
            ->where('payment_status', '!=', 'lunas')
            ->whereNull('payment_received_date')
            ->orderByRaw('CASE WHEN payment_due_date IS NULL THEN 1 ELSE 0 END, payment_due_date ASC, updated_at DESC');

        $waitingPayment = (int) (clone $waitingPaymentItemsQuery)->count();
        $waitingPaymentItems = (clone $waitingPaymentItemsQuery)
            ->limit(10)
            ->get()
            ->map(fn (Endorsement $endorsement) => $this->serializeDashboardItem($endorsement));

        $selectedStatusItemsQuery = Endorsement::query()
            ->where('user_id', $userId)
            ->where('status', $selectedStatus)
            ->orderByDesc('updated_at');

        if ($statusSearch !== '') {
            $selectedStatusItemsQuery->where(function ($builder) use ($statusSearch): void {
                $builder->where('brand_name', 'like', '%'.$statusSearch.'%')
                    ->orWhere('campaign_name', 'like', '%'.$statusSearch.'%');
            });
        }

        $selectedStatusItems = $selectedStatusItemsQuery
            ->paginate($statusPerPage)
            ->withQueryString()
            ->through(fn (Endorsement $endorsement) => $this->serializeDashboardItem($endorsement));

        $monthFormat = match (config('database.default')) {
            'sqlsrv' => "FORMAT(created_at, 'yyyy-MM-01')",
            'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m-01')",
            default => "strftime('%Y-%m-01', created_at)",
        };

        $rawMonthlyStats = Endorsement::query()
            ->where('user_id', $userId)
            ->selectRaw("$monthFormat as month_key")
            ->selectRaw('SUM(fee_amount + reimburse_amount) as income')
            ->selectRaw('SUM(product_cost + other_cost) as cost')
            ->groupByRaw($monthFormat)
            ->orderByRaw($monthFormat)
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
            'priorityItems' => $this->buildPriorityItems($userId),
            'selectedStatus' => $selectedStatus,
            'selectedStatusItems' => $selectedStatusItems,
            'selectedStatusFilters' => [
                'status_search' => $statusSearch,
                'status_per_page' => $statusPerPage,
            ],
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
            'total_income' => (float) $endorsement->total_income,
            'total_cost' => (float) $endorsement->total_cost,
            'net_profit' => (float) $endorsement->net_profit,
        ];
    }

    private function buildPriorityItems(int $userId): array
    {
        $today = Carbon::today();
        $soon = $today->copy()->addDays(7);
        $items = collect();

        $items = $items->merge(
            Endorsement::query()
                ->where('user_id', $userId)
                ->whereIn('status', ['pembuatan_draft', 'menunggu_draft_ok', 'revisi'])
                ->whereNotNull('draft_deadline')
                ->whereDate('draft_deadline', '<=', $today)
                ->orderBy('draft_deadline')
                ->limit(5)
                ->get()
                ->map(fn (Endorsement $endorsement) => $this->serializePriorityItem(
                    $endorsement,
                    'draft',
                    'Deadline draft',
                    'Draft atau revisi perlu diselesaikan',
                    $endorsement->draft_deadline,
                ))
        );

        $items = $items->merge(
            Endorsement::query()
                ->where('user_id', $userId)
                ->where('status', '!=', 'selesai')
                ->whereNull('posted_at')
                ->whereNotNull('posting_date')
                ->whereDate('posting_date', '<=', $today)
                ->orderBy('posting_date')
                ->limit(5)
                ->get()
                ->map(fn (Endorsement $endorsement) => $this->serializePriorityItem(
                    $endorsement,
                    'posting',
                    'Jadwal posting',
                    'Konten perlu diposting atau dikonfirmasi tayang',
                    $endorsement->posting_date,
                ))
        );

        $items = $items->merge(
            Endorsement::query()
                ->where('user_id', $userId)
                ->whereNull('insight_sent_at')
                ->whereNotNull('insight_due_at')
                ->whereDate('insight_due_at', '<=', $today)
                ->orderBy('insight_due_at')
                ->limit(5)
                ->get()
                ->map(fn (Endorsement $endorsement) => $this->serializePriorityItem(
                    $endorsement,
                    'insight',
                    'Laporan insight',
                    'Insight sudah waktunya dikirim ke brand',
                    $endorsement->insight_due_at,
                ))
        );

        $items = $items->merge(
            Endorsement::query()
                ->where('user_id', $userId)
                ->where('payment_status', '!=', 'lunas')
                ->whereNull('payment_received_date')
                ->whereNotNull('payment_due_date')
                ->whereDate('payment_due_date', '<=', $today)
                ->orderBy('payment_due_date')
                ->limit(5)
                ->get()
                ->map(fn (Endorsement $endorsement) => $this->serializePriorityItem(
                    $endorsement,
                    'payment',
                    'Tagih payment',
                    'Pembayaran perlu difollow up',
                    $endorsement->payment_due_date,
                    (float) $endorsement->total_income,
                ))
        );

        $boostcodeItems = Endorsement::query()
            ->where('user_id', $userId)
            ->where('boostcode_required', true)
            ->whereNotNull('posted_at')
            ->whereNotNull('boostcode_duration_days')
            ->orderBy('posted_at')
            ->limit(50)
            ->get()
            ->filter(fn (Endorsement $endorsement) => $endorsement->boostcode_deadline
                && $endorsement->boostcode_deadline->betweenIncluded($today, $soon))
            ->map(fn (Endorsement $endorsement) => $this->serializePriorityItem(
                $endorsement,
                'boostcode',
                'Boostcode hampir selesai',
                'Masa boostcode akan habis dalam 7 hari',
                $endorsement->boostcode_deadline,
            ));

        return $items
            ->merge($boostcodeItems)
            ->sortBy(fn (array $item) => $item['due_at'] ?? '9999-12-31')
            ->take(12)
            ->values()
            ->all();
    }

    private function serializePriorityItem(
        Endorsement $endorsement,
        string $type,
        string $label,
        string $title,
        ?Carbon $dueAt,
        ?float $amount = null,
    ): array {
        return [
            'id' => $endorsement->id,
            'type' => $type,
            'label' => $label,
            'title' => $title,
            'brand_name' => $endorsement->brand_name,
            'campaign_name' => $endorsement->campaign_name,
            'status' => $endorsement->status,
            'status_label' => Endorsement::STATUS_OPTIONS[$endorsement->status] ?? $endorsement->status,
            'due_at' => optional($dueAt)->format('Y-m-d'),
            'amount' => $amount,
            'url' => route('endorsements.show', $endorsement),
        ];
    }
}
