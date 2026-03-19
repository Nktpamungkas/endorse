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
                <label class="form-label small text-muted-soft">Filter payment</label>
                <select class="form-select" name="payment_status">
                    <option value="">Semua</option>
                    @foreach(\App\Models\Endorsement::PAYMENT_STATUS_OPTIONS as $key => $label)
                        <option value="{{ $key }}" @selected(request('payment_status') === $key)>{{ $label }}</option>
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
            <div class="col-md-2 d-flex justify-content-end">
                <a href="{{ route('endorsements.export', request()->query()) }}" class="btn btn-outline-dark w-100">Download CSV</a>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted-soft">Per halaman</label>
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach([10,25,50,100] as $pp)
                        <option value="{{ $pp }}" @selected((int)request('per_page', $perPage) === $pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-border bg-white p-3 shadow-sm">
        <div class="overflow-x-auto hidden lg:block max-h-[70vh]">
            <table class="min-w-full text-sm">
                <thead class="text-muted-foreground border-b border-border">
                <tr>
                    <th class="py-2 text-left">Brand / Campaign</th>
                    <th class="py-2 text-left">Platform</th>
                    <th class="py-2 text-left">Status</th>
                    <th class="py-2 text-left">Posting</th>
                    <th class="py-2 text-left">Insight</th>
                    <th class="py-2 text-left">Payment</th>
                    <th class="py-2 text-right">Modal</th>
                    <th class="py-2 text-right">Laba</th>
                    <th class="py-2 text-right">Aksi</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-border">
                @forelse($endorsements as $item)
                    <tr class="hover:bg-muted/40">
                        <td class="py-2">
                            <div class="font-semibold text-foreground">{{ $item->brand_name }}</div>
                            @if($item->campaign_name)
                                <div class="text-xs text-muted-foreground">{{ $item->campaign_name }}</div>
                            @endif
                        </td>
                        <td class="py-2 whitespace-nowrap">{{ \App\Models\Endorsement::PLATFORM_OPTIONS[$item->platform] ?? $item->platform }}</td>
                        <td class="py-2 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full bg-muted px-2 py-1 text-xs font-semibold text-foreground">
                                {{ \App\Models\Endorsement::STATUS_OPTIONS[$item->status] ?? $item->status }}
                            </span>
                        </td>
                        <td class="py-2 whitespace-nowrap">{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</td>
                        <td class="py-2 whitespace-nowrap">
                            @if($item->insight_sent_at)
                                <span class="text-emerald-600">Terkirim</span>
                            @elseif($item->insight_due_at)
                                <span class="{{ $item->insight_due_at->isPast() ? 'text-red-600 font-semibold' : 'text-foreground' }}">
                                    {{ $item->insight_due_at->format('d/m/Y') }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-2 whitespace-nowrap">{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}</td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <div class="font-semibold">Rp {{ number_format($item->total_cost, 0, ',', '.') }}</div>
                            <div class="text-xs text-muted-foreground">Produk {{ number_format((float) $item->product_cost, 0, ',', '.') }} | Lain {{ number_format((float) $item->other_cost, 0, ',', '.') }}</div>
                        </td>
                        <td class="py-2 text-right whitespace-nowrap {{ $item->net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                        </td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <div class="inline-flex gap-1">
                                <a href="{{ route('endorsements.show', $item) }}" class="inline-flex items-center rounded-md border border-border px-3 py-1 text-xs font-semibold text-foreground hover:bg-muted">Detail</a>
                                <a href="{{ route('endorsements.edit', $item) }}" class="inline-flex items-center rounded-md bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground hover:bg-primary/90">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-4 text-center text-muted-foreground">Belum ada data endorse.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="lg:hidden mobile-card-list">
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

        <div class="mt-3">
            {{ $endorsements->links() }}
        </div>
    </div>
@endsection
