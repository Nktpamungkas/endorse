<?php

namespace App\Services;

use App\Models\Endorsement;
use App\Repositories\EndorsementRepository;

class TotalModalService
{
    public const SORTS = ['highest_modal', 'lowest_modal', 'latest', 'oldest'];

    public function __construct(private readonly EndorsementRepository $repo) {}

    public function data(int $userId, array $filters, string $sort, int $perPage): array
    {
        $summary = $this->repo->modalSummary($userId, $filters);
        $highest = $summary['highest_modal_item'];

        return [
            'endorsements' => $this->repo->paginateModal($userId, $filters, $sort, $perPage)
                ->through(fn (Endorsement $e) => $this->serialize($e)),
            'summary' => [
                'total_items' => $summary['total_items'],
                'total_modal' => $summary['total_modal'],
                'average_modal' => $summary['total_items'] > 0 ? $summary['total_modal'] / $summary['total_items'] : 0,
                'total_product_cost' => $summary['total_product_cost'],
                'total_other_cost' => $summary['total_other_cost'],
                'highest_modal_item' => $highest ? [
                    'id' => $highest->id,
                    'brand_name' => $highest->brand_name,
                    'campaign_name' => $highest->campaign_name,
                    'total_cost' => (float) $highest->total_cost,
                ] : null,
            ],
            'filters' => [
                'q' => $filters['q'] ?? '',
                'status' => $filters['status'] ?? '',
                'platform' => $filters['platform'] ?? '',
                'sort' => $sort,
                'per_page' => $perPage,
            ],
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
        ];
    }

    private function serialize(Endorsement $e): array
    {
        return [
            'id' => $e->id,
            'brand_name' => $e->brand_name,
            'campaign_name' => $e->campaign_name,
            'platform' => $e->platform,
            'platform_label' => Endorsement::PLATFORM_OPTIONS[$e->platform] ?? $e->platform,
            'status' => $e->status,
            'status_label' => Endorsement::STATUS_OPTIONS[$e->status] ?? $e->status,
            'posting_date' => optional($e->posting_date)->format('Y-m-d'),
            'product_cost' => (float) $e->product_cost,
            'other_cost' => (float) $e->other_cost,
            'total_cost' => (float) $e->total_cost,
            'total_income' => (float) $e->total_income,
            'net_profit' => (float) $e->net_profit,
        ];
    }
}
