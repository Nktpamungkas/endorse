@extends('layouts.app', ['title' => 'Dashboard Endorse'])

@section('content')
    <div class="page-head mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Dashboard Endorse</h1>
            <div class="text-muted-soft">Ringkasan progres TikTok & Instagram.</div>
        </div>
        <a href="{{ route('endorsements.create') }}" class="btn btn-dark">+ Tambah Endorse</a>
    </div>

    <div class="card card-soft p-3 mb-3" style="background: linear-gradient(120deg, #e9f4ff, #fff6e8);">
        <div class="small text-muted-soft">Laba Bersih Total</div>
        <div class="h4 fw-bold mb-0 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
            Rp {{ number_format($netProfit, 0, ',', '.') }}
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-soft p-3">
                <div class="text-muted-soft small">Total Pendapatan</div>
                <div class="h5 fw-bold mb-0">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-soft p-3">
                <div class="text-muted-soft small">Total Modal</div>
                <div class="h5 fw-bold mb-0">Rp {{ number_format($totalCost, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-soft p-3">
                <div class="text-muted-soft small">Menunggu Payment</div>
                <div class="h5 fw-bold mb-0">{{ $waitingPayment }} endorse</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card card-soft p-3 h-100">
                <h2 class="h6 fw-bold">Status Endorse</h2>
                <div class="d-grid gap-2 mt-2">
                    @foreach($statusOptions as $key => $label)
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('dashboard', ['status_view' => $key]) }}"
                               class="text-decoration-none {{ $selectedStatus === $key ? 'fw-bold text-dark' : 'text-dark' }}">
                                {{ $label }}
                            </a>
                            <a href="{{ route('dashboard', ['status_view' => $key]) }}"
                               class="badge-status text-decoration-none {{ $selectedStatus === $key ? 'bg-dark text-white' : '' }}">
                                {{ $statusCounts[$key] ?? 0 }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

<div class="card card-soft p-3 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 fw-bold mb-0">
                Detail Status: {{ $statusOptions[$selectedStatus] ?? $selectedStatus }}
            </h2>
            <a href="{{ route('endorsements.index', ['status' => $selectedStatus]) }}" class="btn btn-sm btn-outline-dark">Lihat Semua</a>
        </div>

        <div class="table-responsive desktop-table">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Brand</th>
                    <th>Platform</th>
                    <th>Posting</th>
                    <th>Insight</th>
                    <th>Payment</th>
                    <th class="text-end">Laba Bersih</th>
                    <th class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($selectedStatusItems as $item)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $item->brand_name }}</div>
                            @if($item->campaign_name)
                                <div class="small text-muted-soft">{{ $item->campaign_name }}</div>
                            @endif
                        </td>
                        <td>{{ \App\Models\Endorsement::PLATFORM_OPTIONS[$item->platform] ?? $item->platform }}</td>
                        <td>{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            @if($item->insight_sent_at)
                                <span class="text-success">Terkirim</span>
                            @elseif($item->insight_due_at)
                                <span class="{{ $item->insight_due_at->isPast() ? 'text-danger fw-semibold' : '' }}">
                                    {{ $item->insight_due_at->format('d/m/Y') }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}</td>
                        <td class="text-end {{ $item->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('endorsements.status.update', $item) }}" class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                                @csrf
                                <select name="status" class="form-select form-select-sm" style="max-width: 180px">
                                    @foreach($statusOptions as $key => $label)
                                        <option value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-dark">Update</button>
                                <a href="{{ route('endorsements.show', $item) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted-soft py-3">Tidak ada job di status ini.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mobile-card-list">
            @forelse($selectedStatusItems as $item)
                <div class="mobile-endorse-card">
                    <div class="fw-semibold">{{ $item->brand_name }}</div>
                    @if($item->campaign_name)
                        <div class="small text-muted-soft">{{ $item->campaign_name }}</div>
                    @endif

                    <div class="mobile-endorse-grid">
                        <div><span class="text-muted-soft">Platform</span><br>{{ \App\Models\Endorsement::PLATFORM_OPTIONS[$item->platform] ?? $item->platform }}</div>
                        <div><span class="text-muted-soft">Payment</span><br>{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}</div>
                        <div><span class="text-muted-soft">Posting</span><br>{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</div>
                        <div>
                            <span class="text-muted-soft">Insight</span><br>
                            @if($item->insight_sent_at)
                                <span class="text-success">Terkirim</span>
                            @elseif($item->insight_due_at)
                                <span class="{{ $item->insight_due_at->isPast() ? 'text-danger fw-semibold' : '' }}">
                                    {{ $item->insight_due_at->format('d/m/Y') }}
                                </span>
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <div class="mt-2 fw-semibold {{ $item->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                        Laba Bersih: Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                    </div>

                    <div class="mobile-endorse-actions">
                        <form method="POST" action="{{ route('endorsements.status.update', $item) }}" class="d-grid gap-2">
                            @csrf
                            <select name="status" class="form-select form-select-sm">
                                @foreach($statusOptions as $key => $label)
                                    <option value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-dark btn-sm">Update Status</button>
                        </form>
                        <a href="{{ route('endorsements.show', $item) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                    </div>
                </div>
            @empty
                <div class="text-muted-soft small">Tidak ada job di status ini.</div>
            @endforelse
    </div>
</div>

{{-- Floating WhatsApp contact --}}
<a href="https://wa.me/6285156637499?text=Halo%20kak%2C%20saya%20ingin%20bertanya%20tentang%20Endorse%20Tracker.%20Mohon%20info%20lebih%20lanjut."
   target="_blank" rel="noopener"
   style="position: fixed; right: 18px; bottom: 18px; z-index: 1050; text-decoration: none;">
    <div style="background:#25D366; color:white; border-radius:50%; width:56px; height:56px; display:flex; align-items:center; justify-content:center; box-shadow:0 12px 30px rgba(0,0,0,0.18);">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 21l1.65-3.8a9 9 0 111.6 1.6L3 21z"></path>
            <path d="M8.5 9.5a5 5 0 007 7"></path>
        </svg>
    </div>
</a>
@endsection
