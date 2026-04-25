@extends('layouts.app', ['title' => 'Data Endorse'])

@section('content')
    <div class="page-head mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Data endorse</h1>
            <div class="text-muted-soft">Semua campaign ada di sini, dari yang baru masuk sampai yang sudah dibayar.</div>
        </div>
        <a href="{{ route('endorsements.create') }}" class="btn btn-dark">Tambah data endorse</a>
    </div>

    <div class="card card-soft p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted-soft">Cari brand atau campaign</label>
                <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="contoh: Wardah">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted-soft">Status pekerjaan</label>
                <select class="form-select" name="status">
                    <option value="">Semua status</option>
                    @foreach($statusOptions as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted-soft">Status payment</label>
                <select class="form-select" name="payment_status">
                    <option value="">Semua</option>
                    @foreach(\App\Models\Endorsement::PAYMENT_STATUS_OPTIONS as $key => $label)
                        <option value="{{ $key }}" @selected(request('payment_status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted-soft">Status insight</label>
                <select class="form-select" name="insight">
                    <option value="">Semua</option>
                    <option value="waiting" @selected($insightFilter === 'waiting')>Menunggu insight</option>
                    <option value="overdue" @selected($insightFilter === 'overdue')>Terlambat insight</option>
                    <option value="sent" @selected($insightFilter === 'sent')>Insight sudah dikirim</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100">Terapkan filter</button>
                <a href="{{ route('endorsements.index') }}" class="btn btn-outline-secondary w-100">Hapus filter</a>
            </div>
            <div class="col-md-2 d-flex justify-content-end">
                <a href="{{ route('endorsements.export', request()->query()) }}" class="btn btn-outline-dark w-100">Unduh CSV</a>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted-soft">Tampilan per halaman</label>
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach([10,25,50,100] as $pp)
                        <option value="{{ $pp }}" @selected((int)request('per_page', $perPage) === $pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="card card-soft p-3">
        <div class="overflow-x-auto hidden lg:block max-h-[70vh]">
            <table class="min-w-full text-sm">
                <thead class="text-muted-foreground border-b border-border">
                <tr>
                    <th class="py-2 text-left">Brand / campaign</th>
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
                            <span class="badge-soft is-neutral">
                                {{ \App\Models\Endorsement::STATUS_OPTIONS[$item->status] ?? $item->status }}
                            </span>
                        </td>
                        <td class="py-2 whitespace-nowrap">{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</td>
                        <td class="py-2 whitespace-nowrap">
                            @if($item->insight_sent_at)
                                <span class="badge-soft is-success">Sudah dikirim</span>
                            @elseif($item->insight_due_at)
                                <span class="badge-soft {{ $item->insight_due_at->isPast() ? 'is-danger' : 'is-info' }}">
                                    {{ $item->insight_due_at->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="badge-soft is-neutral">Tidak ada</span>
                            @endif
                        </td>
                        <td class="py-2 whitespace-nowrap">
                            <span class="badge-soft is-neutral">
                                {{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}
                            </span>
                        </td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <div class="font-semibold">Rp {{ number_format($item->total_cost, 0, ',', '.') }}</div>
                            <div class="text-xs text-muted-foreground">Produk {{ number_format((float) $item->product_cost, 0, ',', '.') }} | Lain {{ number_format((float) $item->other_cost, 0, ',', '.') }}</div>
                        </td>
                        <td class="py-2 text-right whitespace-nowrap {{ $item->net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                        </td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <div class="inline-flex gap-1">
                                <a href="{{ route('endorsements.show', $item) }}" class="inline-flex items-center rounded-md border border-border px-3 py-1 text-xs font-semibold text-foreground hover:bg-muted">Lihat detail</a>
                                <a href="{{ route('endorsements.edit', $item) }}" class="inline-flex items-center rounded-md bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground hover:bg-primary/90">Ubah data</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-5">
                            <div class="empty-state">
                                <div class="empty-state-title">Belum ada data endorse</div>
                                <p class="empty-state-text">Klik tombol tambah untuk mulai mencatat campaign pertama.</p>
                                <div class="mt-3">
                                    <a href="{{ route('endorsements.create') }}" class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Tambah data endorse</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="lg:hidden mobile-card-list">
            @forelse($endorsements as $item)
                <div class="rounded-xl border border-border bg-white p-3 shadow-sm mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold">{{ $item->brand_name }}</div>
                            @if($item->campaign_name)
                                <div class="small text-muted-soft">{{ $item->campaign_name }}</div>
                            @endif
                        </div>
                        <span class="badge-soft is-neutral">{{ \App\Models\Endorsement::STATUS_OPTIONS[$item->status] ?? $item->status }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground mt-2">
                        <div><span class="block">Platform</span><span class="text-foreground font-semibold">{{ \App\Models\Endorsement::PLATFORM_OPTIONS[$item->platform] ?? $item->platform }}</span></div>
                        <div><span class="block">Payment</span><span class="text-foreground font-semibold">{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}</span></div>
                        <div><span class="block">Posting</span><span class="text-foreground font-semibold">{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</span></div>
                        <div><span class="block">Modal</span><span class="text-foreground font-semibold">Rp {{ number_format($item->total_cost, 0, ',', '.') }}</span></div>
                        <div class="col-span-2">
                            <span class="block">Insight</span>
                            @if($item->insight_sent_at)
                                <span class="badge-soft is-success mt-1">Sudah dikirim</span>
                            @elseif($item->insight_due_at)
                                <span class="badge-soft {{ $item->insight_due_at->isPast() ? 'is-danger' : 'is-info' }} mt-1">
                                    {{ $item->insight_due_at->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="badge-soft is-neutral mt-1">Tidak ada</span>
                            @endif
                        </div>
                    </div>

                    <div class="mobile-finance-row mt-3 d-flex justify-content-between gap-3">
                        <div>
                            <div class="label">Detail modal</div>
                            <div>Produk {{ number_format((float) $item->product_cost, 0, ',', '.') }} | Lain {{ number_format((float) $item->other_cost, 0, ',', '.') }}</div>
                        </div>
                        <div class="text-md-end">
                            <div class="label">Laba</div>
                            <div class="fw-semibold {{ $item->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div class="mobile-endorse-actions mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ route('endorsements.show', $item) }}" class="btn btn-outline-dark btn-sm">Lihat detail</a>
                        <a href="{{ route('endorsements.edit', $item) }}" class="btn btn-outline-primary btn-sm">Ubah data</a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-state-title">Belum ada data endorse</div>
                    <p class="empty-state-text">Klik tombol tambah untuk mulai mencatat campaign pertama.</p>
                    <div class="mt-3">
                        <a href="{{ route('endorsements.create') }}" class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Tambah data endorse</a>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-3">
            {{ $endorsements->links() }}
        </div>
    </div>
@endsection
