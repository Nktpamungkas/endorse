<?php

namespace App\Repositories;

use App\Models\Endorsement;
use App\Models\EndorsementActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat query Eloquent untuk Endorsement.
 * Service & Controller tidak boleh menyentuh Eloquent langsung.
 */
class EndorsementRepository
{
    /** Kartu aktif milik user untuk board Kanban (tanpa paginate — board butuh semua). */
    public function activeForBoard(int $userId, array $filters = []): Collection
    {
        return $this->applyFilters(
            Endorsement::query()->where('user_id', $userId),
            $filters,
        )
            ->orderByRaw('CASE WHEN deal_date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('deal_date')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findForUser(int $id, int $userId): ?Endorsement
    {
        return Endorsement::query()->where('user_id', $userId)->find($id);
    }

    public function findWithRelations(int $id, int $userId): ?Endorsement
    {
        return Endorsement::query()
            ->where('user_id', $userId)
            ->with(['revisions', 'deletedBy'])
            ->find($id);
    }

    public function create(array $data): Endorsement
    {
        return Endorsement::create($data);
    }

    public function update(Endorsement $endorsement, array $data): Endorsement
    {
        $endorsement->update($data);

        return $endorsement;
    }

    public function trashedForUser(int $userId, array $filters = []): Collection
    {
        return $this->applyKeyword(
            Endorsement::onlyTrashed()->where('user_id', $userId)->with('deletedBy'),
            $filters,
        )->orderByDesc('deleted_at')->get();
    }

    public function findTrashed(int $id, int $userId): ?Endorsement
    {
        return Endorsement::onlyTrashed()
            ->where('user_id', $userId)
            ->with(['revisions', 'deletedBy'])
            ->find($id);
    }

    /** Untuk export CSV — koleksi terfilter tanpa paginate. */
    public function exportForUser(int $userId, array $filters = []): Collection
    {
        return $this->applyFilters(
            Endorsement::query()->where('user_id', $userId),
            $filters,
        )->orderByDesc('updated_at')->get();
    }

    public function reassignStatus(int $userId, string $fromStatus, string $toStatus): int
    {
        return Endorsement::where('user_id', $userId)
            ->where('status', $fromStatus)
            ->update(['status' => $toStatus]);
    }

    public function logActivity(int $endorsementId, int $userId, string $action, array $meta = []): void
    {
        EndorsementActivity::create([
            'endorsement_id' => $endorsementId,
            'user_id' => $userId,
            'action' => $action,
            'meta' => $meta,
        ]);
    }

    public function recentActivities(Endorsement $endorsement, int $limit = 15): Collection
    {
        return $endorsement->activities()->limit($limit)->get();
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $query = $this->applyKeyword($query, $filters);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $insight = $filters['insight'] ?? '';
        if ($insight === 'waiting') {
            $query->whereNotNull('insight_due_at')->whereNull('insight_sent_at');
        } elseif ($insight === 'overdue') {
            $query->whereDate('insight_due_at', '<', now()->toDateString())->whereNull('insight_sent_at');
        } elseif ($insight === 'sent') {
            $query->whereNotNull('insight_sent_at');
        }

        return $query;
    }

    private function applyKeyword(Builder $query, array $filters): Builder
    {
        if (! empty($filters['q'])) {
            $keyword = $filters['q'];
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('brand_name', 'like', '%'.$keyword.'%')
                    ->orWhere('campaign_name', 'like', '%'.$keyword.'%');
            });
        }

        return $query;
    }

    // ---------- Reporting (Saldo, Neraca, TotalModal, Dashboard) ----------

    private const NET_PROFIT_RAW = '(fee_amount + reimburse_amount) - (product_cost + other_cost)';

    /** Endorsement yang dianggap sudah dibayar (dipakai Saldo/Neraca/Dashboard). */
    private function paid(Builder $query): Builder
    {
        return $query->where(function ($q): void {
            $q->where('payment_status', 'lunas')
                ->orWhere('status', 'selesai')
                ->orWhereNotNull('payment_received_date');
        });
    }

    public function paidNetProfit(int $userId): float
    {
        return (float) $this->paid(Endorsement::query()->where('user_id', $userId))
            ->sum(DB::raw(self::NET_PROFIT_RAW));
    }

    public function paidNetProfitBefore(int $userId, Carbon $date): float
    {
        return (float) $this->paid(Endorsement::query()->where('user_id', $userId))
            ->where('created_at', '<', $date)
            ->sum(DB::raw(self::NET_PROFIT_RAW));
    }

    public function paidInPeriod(int $userId, int $bulan, int $tahun): Collection
    {
        $query = $this->paid(Endorsement::query()->where('user_id', $userId));
        $this->applyPeriod($query, 'created_at', $bulan, $tahun);

        return $query->get();
    }

    public function sumIncome(int $userId): float
    {
        return (float) Endorsement::where('user_id', $userId)->sum(DB::raw('fee_amount + reimburse_amount'));
    }

    public function sumCost(int $userId): float
    {
        return (float) Endorsement::where('user_id', $userId)->sum(DB::raw('product_cost + other_cost'));
    }

    public function statusCounts(int $userId)
    {
        return Endorsement::query()
            ->where('user_id', $userId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    /** Statistik bulanan keyed by 'Y-m-01' (income & cost). */
    public function monthlyStatsKeyed(int $userId)
    {
        $monthFormat = match (config('database.default')) {
            'sqlsrv' => "FORMAT(created_at, 'yyyy-MM-01')",
            'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m-01')",
            default => "strftime('%Y-%m-01', created_at)",
        };

        return Endorsement::query()
            ->where('user_id', $userId)
            ->selectRaw("$monthFormat as month_key")
            ->selectRaw('SUM(fee_amount + reimburse_amount) as income')
            ->selectRaw('SUM(product_cost + other_cost) as cost')
            ->groupByRaw($monthFormat)
            ->orderByRaw($monthFormat)
            ->get()
            ->keyBy('month_key');
    }

    private function waitingPaymentQuery(int $userId): Builder
    {
        return Endorsement::query()
            ->where('user_id', $userId)
            ->where('status', 'menunggu_payment')
            ->where('payment_status', '!=', 'lunas')
            ->whereNull('payment_received_date')
            ->orderByRaw('CASE WHEN payment_due_date IS NULL THEN 1 ELSE 0 END, payment_due_date ASC, updated_at DESC');
    }

    public function waitingPaymentCount(int $userId): int
    {
        return (int) $this->waitingPaymentQuery($userId)->count();
    }

    public function waitingPaymentItems(int $userId, int $limit = 10): Collection
    {
        return $this->waitingPaymentQuery($userId)->limit($limit)->get();
    }

    public function paginateByStatus(int $userId, string $status, string $search, int $perPage): LengthAwarePaginator
    {
        $query = Endorsement::query()
            ->where('user_id', $userId)
            ->where('status', $status)
            ->orderByDesc('updated_at');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('brand_name', 'like', '%'.$search.'%')
                    ->orWhere('campaign_name', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginateModal(int $userId, array $filters, string $sort, int $perPage): LengthAwarePaginator
    {
        $query = $this->modalBaseQuery($userId, $filters);

        match ($sort) {
            'lowest_modal' => $query->orderByRaw('(product_cost + other_cost) ASC')->orderByDesc('updated_at'),
            'latest' => $query->orderByDesc('updated_at'),
            'oldest' => $query->orderBy('updated_at'),
            default => $query->orderByRaw('(product_cost + other_cost) DESC')->orderByDesc('updated_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function modalSummary(int $userId, array $filters): array
    {
        $total = (clone $this->modalBaseQuery($userId, $filters))->count();
        $highest = (clone $this->modalBaseQuery($userId, $filters))
            ->orderByRaw('(product_cost + other_cost) DESC')
            ->orderByDesc('updated_at')
            ->first();

        return [
            'total_items' => $total,
            'total_modal' => (float) (clone $this->modalBaseQuery($userId, $filters))->sum(DB::raw('product_cost + other_cost')),
            'total_product_cost' => (float) (clone $this->modalBaseQuery($userId, $filters))->sum('product_cost'),
            'total_other_cost' => (float) (clone $this->modalBaseQuery($userId, $filters))->sum('other_cost'),
            'highest_modal_item' => $highest,
        ];
    }

    private function modalBaseQuery(int $userId, array $filters): Builder
    {
        $query = Endorsement::query()->where('user_id', $userId);
        $this->applyKeyword($query, $filters);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        return $query;
    }

    // Priority queries untuk Dashboard.
    public function priorityDrafts(int $userId, Carbon $today): Collection
    {
        return Endorsement::query()->where('user_id', $userId)
            ->whereIn('status', ['pembuatan_draft', 'menunggu_draft_ok', 'revisi'])
            ->whereNotNull('draft_deadline')
            ->whereDate('draft_deadline', '<=', $today)
            ->orderBy('draft_deadline')->limit(5)->get();
    }

    public function priorityPostings(int $userId, Carbon $today): Collection
    {
        return Endorsement::query()->where('user_id', $userId)
            ->where('status', '!=', 'selesai')
            ->whereNull('posted_at')
            ->whereNotNull('posting_date')
            ->whereDate('posting_date', '<=', $today)
            ->orderBy('posting_date')->limit(5)->get();
    }

    public function priorityInsights(int $userId, Carbon $today): Collection
    {
        return Endorsement::query()->where('user_id', $userId)
            ->whereNull('insight_sent_at')
            ->whereNotNull('insight_due_at')
            ->whereDate('insight_due_at', '<=', $today)
            ->orderBy('insight_due_at')->limit(5)->get();
    }

    public function priorityPayments(int $userId, Carbon $today): Collection
    {
        return Endorsement::query()->where('user_id', $userId)
            ->where('payment_status', '!=', 'lunas')
            ->whereNull('payment_received_date')
            ->whereNotNull('payment_due_date')
            ->whereDate('payment_due_date', '<=', $today)
            ->orderBy('payment_due_date')->limit(5)->get();
    }

    public function boostcodeCandidates(int $userId): Collection
    {
        return Endorsement::query()->where('user_id', $userId)
            ->where('boostcode_required', true)
            ->whereNotNull('posted_at')
            ->whereNotNull('boostcode_duration_days')
            ->orderBy('posted_at')->limit(50)->get();
    }

    private function applyPeriod(Builder $query, string $column, int $bulan, int $tahun): void
    {
        if ($tahun === 0) {
            return;
        }

        if ($bulan > 0) {
            $query->whereYear($column, $tahun)->whereMonth($column, $bulan);
        } else {
            $query->whereYear($column, $tahun);
        }
    }
}
