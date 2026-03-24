<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TotalModalController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $perPage = max(5, min((int) $request->integer('per_page', 10), 100));
        $sort = (string) $request->string('sort', 'highest_modal');
        if (! in_array($sort, ['highest_modal', 'lowest_modal', 'latest', 'oldest'], true)) {
            $sort = 'highest_modal';
        }

        $query = Endorsement::query()->where('user_id', Auth::id());

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('brand_name', 'like', '%'.$keyword.'%')
                    ->orWhere('campaign_name', 'like', '%'.$keyword.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->string('platform'));
        }

        $summaryQuery = clone $query;
        $itemsQuery = clone $query;

        match ($sort) {
            'lowest_modal' => $itemsQuery
                ->orderByRaw('(product_cost + other_cost) ASC')
                ->orderByDesc('updated_at'),
            'latest' => $itemsQuery->orderByDesc('updated_at'),
            'oldest' => $itemsQuery->orderBy('updated_at'),
            default => $itemsQuery
                ->orderByRaw('(product_cost + other_cost) DESC')
                ->orderByDesc('updated_at'),
        };

        $endorsements = $itemsQuery->paginate($perPage)->withQueryString()
            ->through(fn (Endorsement $endorsement) => $this->serializeItem($endorsement));

        $totalItems = (clone $summaryQuery)->count();
        $totalModal = (float) (clone $summaryQuery)->sum(DB::raw('product_cost + other_cost'));
        $totalProductCost = (float) (clone $summaryQuery)->sum('product_cost');
        $totalOtherCost = (float) (clone $summaryQuery)->sum('other_cost');
        $highestModalItem = (clone $summaryQuery)
            ->orderByRaw('(product_cost + other_cost) DESC')
            ->orderByDesc('updated_at')
            ->first();

        return Inertia::render('TotalModal', [
            'endorsements' => $endorsements,
            'summary' => [
                'total_items' => $totalItems,
                'total_modal' => $totalModal,
                'average_modal' => $totalItems > 0 ? $totalModal / $totalItems : 0,
                'total_product_cost' => $totalProductCost,
                'total_other_cost' => $totalOtherCost,
                'highest_modal_item' => $highestModalItem ? [
                    'id' => $highestModalItem->id,
                    'brand_name' => $highestModalItem->brand_name,
                    'campaign_name' => $highestModalItem->campaign_name,
                    'total_cost' => (float) $highestModalItem->total_cost,
                ] : null,
            ],
            'filters' => [
                'q' => (string) $request->string('q'),
                'status' => (string) $request->string('status'),
                'platform' => (string) $request->string('platform'),
                'sort' => $sort,
                'per_page' => $perPage,
            ],
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
        ]);
    }

    private function serializeItem(Endorsement $endorsement): array
    {
        return [
            'id' => $endorsement->id,
            'brand_name' => $endorsement->brand_name,
            'campaign_name' => $endorsement->campaign_name,
            'platform' => $endorsement->platform,
            'platform_label' => Endorsement::PLATFORM_OPTIONS[$endorsement->platform] ?? $endorsement->platform,
            'status' => $endorsement->status,
            'status_label' => Endorsement::STATUS_OPTIONS[$endorsement->status] ?? $endorsement->status,
            'posting_date' => optional($endorsement->posting_date)->format('Y-m-d'),
            'product_cost' => (float) $endorsement->product_cost,
            'other_cost' => (float) $endorsement->other_cost,
            'total_cost' => (float) $endorsement->total_cost,
            'total_income' => (float) $endorsement->total_income,
            'net_profit' => (float) $endorsement->net_profit,
        ];
    }
}
