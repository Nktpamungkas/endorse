@extends('layouts.app', ['title' => 'Dashboard Endorse'])

@section('content')
    <div class="page-head mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Dashboard Endorse</h1>
            <div class="text-muted-soft">Ringkasan progres TikTok & Instagram.</div>
        </div>
        <a href="{{ route('endorsements.create') }}" class="btn btn-dark">+ Tambah Endorse</a>
    </div>

    <div class="d-flex gap-2 flex-wrap mb-3">
        <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#chartCollapse" aria-expanded="false" aria-controls="chartCollapse">
            Grafik Tren
        </button>
        <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#statusDetailCollapse" aria-expanded="false" aria-controls="statusDetailCollapse">
            Detail Status
        </button>
    </div>

    <div class="collapse" id="chartCollapse">
        <div class="card card-soft p-3 mb-3">
            <h2 class="h6 fw-bold mb-2">Tren Pendapatan vs Modal</h2>
            <canvas id="incomeChart" height="140"></canvas>
        </div>
    </div>

    <div class="card card-soft p-3 mb-3" style="background: linear-gradient(120deg, #e9f4ff, #fff6e8);">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-muted-soft">Laba Bersih Total</div>
                <div class="h4 fw-bold mb-0 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </div>
            </div>
            <div class="text-end">
                <div class="small text-muted-soft">Laba sudah diterima</div>
                <div class="h5 fw-bold mb-0 text-success">
                    Rp {{ number_format($receivedNetProfit, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Tour Modal --}}
    <div class="modal fade" id="tourModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Tour Endorse Tracker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="tourSteps">
                        <div class="tour-step">
                            <h6 class="fw-bold">1. Pilih paket / minta trial</h6>
                            <p class="text-muted mb-0">Tentukan paket mingguan/bulanan atau hubungi admin untuk aktivasi akun trial.</p>
                        </div>
                        <div class="tour-step d-none">
                            <h6 class="fw-bold">2. Tambah endorse</h6>
                            <p class="text-muted mb-0">Klik <strong>+ Tambah Endorse</strong>, isi brand/campaign, status awal, serta detail keuangan.</p>
                        </div>
                        <div class="tour-step d-none">
                            <h6 class="fw-bold">3. Update status & insight</h6>
                            <p class="text-muted mb-0">Gunakan tombol <strong>Update Status</strong> atau halaman detail untuk memindahkan fase (Draft, Revisi, Posting, Insight, Payment).</p>
                        </div>
                        <div class="tour-step d-none">
                            <h6 class="fw-bold">4. Catat keuangan</h6>
                            <p class="text-muted mb-0">Isi fee, reimburse, modal produk, dan biaya lain. Laba bersih dihitung otomatis di dashboard.</p>
                        </div>
                        <div class="tour-step d-none">
                            <h6 class="fw-bold">5. Export & bereskan trial</h6>
                            <p class="text-muted mb-0">Export laporan ke Excel dari Data Endorse. Akun master bisa hapus data user trial bila selesai.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button class="btn btn-outline-secondary" id="tourPrev">Sebelumnya</button>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button class="btn btn-dark" id="tourNext">Berikutnya</button>
                    </div>
                </div>
            </div>
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
        <div class="col-lg-8">
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
        <div class="col-lg-4">
            <div class="card card-soft p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 fw-bold mb-0">Payment Belum Lunas</h2>
                    <a href="{{ route('endorsements.index', ['status' => 'menunggu_payment']) }}" class="btn btn-sm btn-outline-dark">Lihat Semua</a>
                </div>
                <div class="d-grid gap-2 small" style="max-height: 320px; overflow-y: auto;">
                    @forelse($waitingPaymentItems as $item)
                        <div class="border rounded-3 p-2">
                            <div class="fw-semibold">{{ $item->brand_name }}</div>
                            @if($item->campaign_name)
                                <div class="text-muted-soft">{{ $item->campaign_name }}</div>
                            @endif
                            <div class="d-flex justify-content-between mt-1">
                                <span class="badge-status">{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}</span>
                                <span class="text-muted">{{ optional($item->payment_due_date)->format('d/m/Y') ?? 'Due ?' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted-soft small">Semua payment sudah lunas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="collapse" id="statusDetailCollapse">
        <div class="card card-soft p-3 mt-3">
            <div class="row g-3">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 fw-bold mb-0">Detail Status: {{ $statusOptions[$selectedStatus] ?? $selectedStatus }}</h2>
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
            </div>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (() => {
        const steps = Array.from(document.querySelectorAll('#tourSteps .tour-step'));
        let idx = 0;
        const showStep = () => {
            steps.forEach((el, i) => el.classList.toggle('d-none', i !== idx));
            document.getElementById('tourPrev').disabled = idx === 0;
            document.getElementById('tourNext').textContent = idx === steps.length - 1 ? 'Selesai' : 'Berikutnya';
        };
        document.getElementById('tourPrev').addEventListener('click', () => { if (idx > 0) { idx--; showStep(); }});
        document.getElementById('tourNext').addEventListener('click', () => {
            if (idx < steps.length - 1) { idx++; showStep(); }
            else { bootstrap.Modal.getInstance(document.getElementById('tourModal')).hide(); }
        });
        showStep();
        const storageKey = 'endorse_tour_seen_v1';
        document.getElementById('tourModal').addEventListener('hidden.bs.modal', () => {
            localStorage.setItem(storageKey, '1');
        });
        if (!localStorage.getItem(storageKey)) {
            const autoTour = new bootstrap.Modal(document.getElementById('tourModal'));
            autoTour.show();
        }

        // Chart income vs cost
        const ctx = document.getElementById('incomeChart');
        if (ctx) {
            const labels = @json($monthlyStats->pluck('month_key')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y')));
            const income = @json($monthlyStats->pluck('income')->map(fn($v) => (float) $v));
            const cost = @json($monthlyStats->pluck('cost')->map(fn($v) => (float) $v));
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {label: 'Pendapatan', data: income, backgroundColor: '#0f4c81'},
                        {label: 'Modal', data: cost, backgroundColor: '#f39c12'},
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            labels: {font: {size: 11}, color: '#102544'}
                        }
                    },
                    scales: {
                        x: {ticks: {color: '#5c6b7a'}},
                        y: {
                            ticks: {
                                color: '#5c6b7a',
                                callback: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                            },
                            grid: {color: 'rgba(0,0,0,0.05)'}
                        }
                    }
                }
            });
        }
    })();
</script>
@endpush
