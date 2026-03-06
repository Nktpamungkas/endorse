@extends('layouts.app', ['title' => 'Data Endorse'])

@section('content')
    <div class="page-head mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Data Endorse</h1>
            <div class="text-muted-soft">Tracking campaign, revisi, insight, dan payment.</div>
        </div>
        <a href="{{ route('endorsements.create') }}" class="btn btn-dark">+ Tambah Endorse</a>
    </div>

    <div class="card card-soft p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted-soft">Cari brand/campaign</label>
                <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="contoh: Wardah">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted-soft">Filter status</label>
                <select class="form-select" name="status">
                    <option value="">Semua status</option>
                    @foreach($statusOptions as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted-soft">Filter insight</label>
                <select class="form-select" name="insight">
                    <option value="">Semua</option>
                    <option value="waiting" @selected($insightFilter === 'waiting')>Menunggu Insight</option>
                    <option value="overdue" @selected($insightFilter === 'overdue')>Insight Overdue</option>
                    <option value="sent" @selected($insightFilter === 'sent')>Insight Terkirim</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('endorsements.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>

    <div class="card card-soft p-3">
        <div class="table-responsive desktop-table">
            <table class="table align-middle endorse-table">
                <thead>
                    <tr>
                        <th class="brand-cell">Brand</th>
                        <th>Platform</th>
                        <th>Status</th>
                        <th>Posting</th>
                        <th>Insight Due</th>
                        <th>Payment</th>
                        <th class="text-end">Modal</th>
                        <th class="text-end">Laba</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($endorsements as $item)
                        <tr>
                            <td class="brand-cell">
                                <div class="fw-semibold">{{ $item->brand_name }}</div>
                                @if($item->campaign_name)
                                    <div class="small text-muted-soft">{{ $item->campaign_name }}</div>
                                @endif
                            </td>
                            <td class="cell-nowrap">{{ \App\Models\Endorsement::PLATFORM_OPTIONS[$item->platform] ?? $item->platform }}</td>
                            <td class="cell-nowrap"><span class="badge-status">{{ \App\Models\Endorsement::STATUS_OPTIONS[$item->status] ?? $item->status }}</span></td>
                            <td class="cell-nowrap">{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</td>
                            <td class="cell-nowrap">
                                @if($item->insight_sent_at)
                                    <span class="text-success">Sudah</span>
                                @elseif($item->insight_due_at)
                                    <span class="{{ $item->insight_due_at->isPast() ? 'text-danger' : '' }}">
                                        {{ $item->insight_due_at->format('d/m/Y') }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="cell-nowrap">{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}</td>
                            <td class="text-end cell-modal">
                                <div class="fw-semibold cell-nowrap">
                                    Rp {{ number_format($item->total_cost, 0, ',', '.') }}
                                </div>
                                <div class="modal-breakdown">
                                    Produk {{ number_format((float) $item->product_cost, 0, ',', '.') }}
                                    | Lain {{ number_format((float) $item->other_cost, 0, ',', '.') }}
                                </div>
                            </td>
                            <td class="text-end profit-cell {{ $item->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                            </td>
                            <td class="text-end cell-actions">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('endorsements.show', $item) }}" class="btn btn-sm btn-outline-dark">Detail</a>
                                    <a href="{{ route('endorsements.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted-soft py-4">Belum ada data endorse.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mobile-card-list">
            @forelse($endorsements as $item)
                <div class="mobile-endorse-card">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold">{{ $item->brand_name }}</div>
                            @if($item->campaign_name)
                                <div class="small text-muted-soft">{{ $item->campaign_name }}</div>
                            @endif
                        </div>
                        <span class="badge-status">{{ \App\Models\Endorsement::STATUS_OPTIONS[$item->status] ?? $item->status }}</span>
                    </div>

                    <div class="mobile-endorse-grid">
                        <div><span class="text-muted-soft">Platform</span><br>{{ \App\Models\Endorsement::PLATFORM_OPTIONS[$item->platform] ?? $item->platform }}</div>
                        <div><span class="text-muted-soft">Payment</span><br>{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}</div>
                        <div><span class="text-muted-soft">Posting</span><br>{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</div>
                        <div><span class="text-muted-soft">Modal</span><br>Rp {{ number_format($item->total_cost, 0, ',', '.') }}</div>
                        <div>
                            <span class="text-muted-soft">Insight Due</span><br>
                            @if($item->insight_sent_at)
                                <span class="text-success">Sudah</span>
                            @elseif($item->insight_due_at)
                                <span class="{{ $item->insight_due_at->isPast() ? 'text-danger' : '' }}">
                                    {{ $item->insight_due_at->format('d/m/Y') }}
                                </span>
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <div class="mobile-finance-row">
                        <div>
                            <div class="label">Detail Modal</div>
                            <div>Produk {{ number_format((float) $item->product_cost, 0, ',', '.') }} | Lain {{ number_format((float) $item->other_cost, 0, ',', '.') }}</div>
                        </div>
                        <div class="text-md-end">
                            <div class="label">Laba</div>
                            <div class="fw-semibold {{ $item->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div class="mobile-endorse-actions">
                        <a href="{{ route('endorsements.show', $item) }}" class="btn btn-outline-dark btn-sm">Detail</a>
                        <a href="{{ route('endorsements.edit', $item) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted-soft py-3">Belum ada data endorse.</div>
            @endforelse
        </div>

        {{ $endorsements->links() }}
    </div>
@endsection
