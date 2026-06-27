<?php

namespace App\Services;

use App\Models\Endorsement;
use App\Repositories\EndorsementRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private readonly EndorsementRepository $repo) {}

    public function data(int $userId, string $selectedStatus, string $statusSearch, int $statusPerPage): array
    {
        $totalIncome = $this->repo->sumIncome($userId);
        $totalCost = $this->repo->sumCost($userId);

        return [
            'statusCounts' => $this->repo->statusCounts($userId),
            'totalIncome' => $totalIncome,
            'totalCost' => $totalCost,
            'netProfit' => $totalIncome - $totalCost,
            'receivedNetProfit' => $this->repo->paidNetProfit($userId),
            'waitingPayment' => $this->repo->waitingPaymentCount($userId),
            'waitingPaymentItems' => $this->repo->waitingPaymentItems($userId)
                ->map(fn (Endorsement $e) => $this->serializeItem($e)),
            'priorityItems' => $this->buildPriorityItems($userId),
            'selectedStatus' => $selectedStatus,
            'selectedStatusItems' => $this->repo->paginateByStatus($userId, $selectedStatus, $statusSearch, $statusPerPage)
                ->through(fn (Endorsement $e) => $this->serializeItem($e)),
            'selectedStatusFilters' => [
                'status_search' => $statusSearch,
                'status_per_page' => $statusPerPage,
            ],
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
            'paymentStatusOptions' => Endorsement::PAYMENT_STATUS_OPTIONS,
            'monthlyStats' => $this->monthlyStats($userId),
        ];
    }

    private function monthlyStats(int $userId): Collection
    {
        $raw = $this->repo->monthlyStatsKeyed($userId);

        return collect(range(5, 0))->map(function (int $offset) use ($raw) {
            $monthKey = Carbon::now()->startOfMonth()->subMonths($offset)->format('Y-m-01');
            $row = $raw->get($monthKey);

            return [
                'month_key' => $monthKey,
                'income' => (float) ($row->income ?? 0),
                'cost' => (float) ($row->cost ?? 0),
            ];
        })->values();
    }

    private function serializeItem(Endorsement $e): array
    {
        return [
            'id' => $e->id,
            'brand_name' => $e->brand_name,
            'campaign_name' => $e->campaign_name,
            'platform' => $e->platform,
            'status' => $e->status,
            'posting_date' => optional($e->posting_date)->format('Y-m-d'),
            'insight_due_at' => optional($e->insight_due_at)->format('Y-m-d'),
            'insight_sent_at' => optional($e->insight_sent_at)->format('Y-m-d'),
            'payment_status' => $e->payment_status,
            'payment_due_date' => optional($e->payment_due_date)->format('Y-m-d'),
            'total_income' => (float) $e->total_income,
            'total_cost' => (float) $e->total_cost,
            'net_profit' => (float) $e->net_profit,
        ];
    }

    private function buildPriorityItems(int $userId): array
    {
        $today = Carbon::today();
        $soon = $today->copy()->addDays(7);

        $items = collect()
            ->merge($this->repo->priorityDrafts($userId, $today)->map(
                fn (Endorsement $e) => $this->serializePriority($e, 'draft', 'Deadline draft', 'Draft atau revisi perlu diselesaikan', $e->draft_deadline)
            ))
            ->merge($this->repo->priorityPostings($userId, $today)->map(
                fn (Endorsement $e) => $this->serializePriority($e, 'posting', 'Jadwal posting', 'Konten perlu diposting atau dikonfirmasi tayang', $e->posting_date)
            ))
            ->merge($this->repo->priorityInsights($userId, $today)->map(
                fn (Endorsement $e) => $this->serializePriority($e, 'insight', 'Laporan insight', 'Insight sudah waktunya dikirim ke brand', $e->insight_due_at)
            ))
            ->merge($this->repo->priorityPayments($userId, $today)->map(
                fn (Endorsement $e) => $this->serializePriority($e, 'payment', 'Tagih payment', 'Pembayaran perlu difollow up', $e->payment_due_date, (float) $e->total_income)
            ))
            ->merge($this->repo->boostcodeCandidates($userId)
                ->filter(fn (Endorsement $e) => $e->boostcode_deadline && $e->boostcode_deadline->betweenIncluded($today, $soon))
                ->map(fn (Endorsement $e) => $this->serializePriority($e, 'boostcode', 'Boostcode hampir selesai', 'Masa boostcode akan habis dalam 7 hari', $e->boostcode_deadline))
            );

        return $items
            ->sortBy(fn (array $item) => $item['due_at'] ?? '9999-12-31')
            ->take(12)
            ->values()
            ->all();
    }

    private function serializePriority(Endorsement $e, string $type, string $label, string $title, ?Carbon $dueAt, ?float $amount = null): array
    {
        return [
            'id' => $e->id,
            'type' => $type,
            'label' => $label,
            'title' => $title,
            'brand_name' => $e->brand_name,
            'campaign_name' => $e->campaign_name,
            'status' => $e->status,
            'status_label' => Endorsement::STATUS_OPTIONS[$e->status] ?? $e->status,
            'due_at' => optional($dueAt)->format('Y-m-d'),
            'amount' => $amount,
            'url' => route('endorsements.show', $e),
        ];
    }
}
