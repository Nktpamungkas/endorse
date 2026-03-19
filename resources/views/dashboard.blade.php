@extends('layouts.app', ['title' => 'Dashboard Endorse'])

@section('content')
    <div class="space-y-6">
        {{-- Topbar --}}
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold text-foreground">Dashboard</h1>
                <p class="text-sm text-muted-foreground">Ringkasan endorse, insight, dan payment.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                        data-bs-toggle="modal" data-bs-target="#tourModal">
                    Tour
                </button>
                <a href="{{ route('endorsements.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition">
                    + Tambah Endorse
                </a>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-2">
            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <p class="text-xs text-muted-foreground">Laba Bersih</p>
                <p class="text-2xl font-semibold {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($netProfit, 0, ',', '.') }}</p>
                <p class="text-xs text-muted-foreground mt-1">Sudah diterima: Rp {{ number_format($receivedNetProfit, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <p class="text-xs text-muted-foreground">Total Pendapatan</p>
                <p class="text-2xl font-semibold">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <p class="text-xs text-muted-foreground">Total Modal</p>
                <p class="text-2xl font-semibold">Rp {{ number_format($totalCost, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <p class="text-xs text-muted-foreground">Menunggu Payment</p>
                <p class="text-2xl font-semibold">{{ $waitingPayment }} endorse</p>
            </div>
        </div>

        {{-- Chart --}}
        <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="text-xs text-muted-foreground">Tren</p>
                    <h2 class="text-sm font-semibold text-foreground">Pendapatan vs Modal (bulanan)</h2>
                </div>
            </div>
            <div class="h-36">
                <canvas id="incomeChart" height="90"></canvas>
            </div>
        </div>

        {{-- Status + Payment --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 rounded-xl border border-border bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-foreground">Status Endorse</h2>
                    <span class="text-xs text-muted-foreground">Klik untuk filter</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($statusOptions as $key => $label)
                        <a href="{{ route('dashboard', ['status_view' => $key]) }}"
                           class="flex items-center justify-between rounded-lg border border-border px-3 py-2 hover:border-primary/60 hover:bg-muted/60 transition {{ $selectedStatus === $key ? 'bg-primary/5 ring-1 ring-primary/40' : '' }}">
                            <span class="font-medium text-sm">{{ $label }}</span>
                            <span class="inline-flex items-center justify-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedStatus === $key ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground' }}">
                                {{ $statusCounts[$key] ?? 0 }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-foreground">Payment Belum Lunas</h2>
                    <a href="{{ route('endorsements.index', ['status' => 'menunggu_payment']) }}"
                       class="text-xs font-semibold text-primary hover:underline">Lihat</a>
                </div>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($waitingPaymentItems as $item)
                        <div class="rounded-lg border border-border/60 p-3 bg-white">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-foreground">{{ $item->brand_name }}</p>
                                    @if($item->campaign_name)
                                        <p class="text-xs text-muted-foreground">{{ $item->campaign_name }}</p>
                                    @endif
                                </div>
                                <span class="text-xs text-muted-foreground">
                                    {{ optional($item->payment_due_date)->format('d/m/Y') ?? 'Due ?' }}
                                </span>
                            </div>
                            <div class="mt-2 inline-flex items-center gap-2 text-xs">
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-amber-700">
                                    {{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}
                                </span>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-700">
                                    Rp {{ number_format($item->total_cost, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted-foreground">Semua payment sudah lunas.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Detail status --}}
        <div class="rounded-xl border border-border bg-white p-4 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-muted-foreground uppercase tracking-[0.15em]">Detail Status</p>
                    <h3 class="text-lg font-semibold">{{ $statusOptions[$selectedStatus] ?? $selectedStatus }}</h3>
                </div>
                <a href="{{ route('endorsements.index', ['status' => $selectedStatus]) }}"
                   class="text-sm font-semibold text-primary hover:underline">Lihat di Data Endorse</a>
            </div>

            <div class="hidden lg:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-muted-foreground">
                        <tr class="border-b border-border">
                            <th class="py-2">Brand</th>
                            <th class="py-2">Platform</th>
                            <th class="py-2">Posting</th>
                            <th class="py-2">Insight</th>
                            <th class="py-2">Payment</th>
                            <th class="py-2 text-right">Laba Bersih</th>
                            <th class="py-2 text-right">Aksi</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                        @forelse($selectedStatusItems as $item)
                            <tr class="hover:bg-muted/40 transition">
                                <td class="py-2">
                                    <div class="font-semibold">{{ $item->brand_name }}</div>
                                    @if($item->campaign_name)
                                        <div class="text-xs text-muted-foreground">{{ $item->campaign_name }}</div>
                                    @endif
                                </td>
                                <td class="py-2">{{ \App\Models\Endorsement::PLATFORM_OPTIONS[$item->platform] ?? $item->platform }}</td>
                                <td class="py-2">{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</td>
                                <td class="py-2">
                                    @if($item->insight_sent_at)
                                        <span class="text-emerald-600 font-semibold">Terkirim</span>
                                    @elseif($item->insight_due_at)
                                        <span class="{{ $item->insight_due_at->isPast() ? 'text-red-600 font-semibold' : 'text-foreground' }}">
                                            {{ $item->insight_due_at->format('d/m/Y') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-2">{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}</td>
                                <td class="py-2 text-right {{ $item->net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                                </td>
                                <td class="py-2 text-right">
                                    <form method="POST" action="{{ route('endorsements.status.update', $item) }}" class="inline-flex items-center gap-2">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm" style="max-width: 170px">
                                            @foreach($statusOptions as $key => $label)
                                                <option value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="inline-flex items-center justify-center rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90">Update</button>
                                        <a href="{{ route('endorsements.show', $item) }}" class="inline-flex items-center justify-center rounded-md border border-border px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-muted">Detail</a>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-muted-foreground">Tidak ada job di status ini.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="grid grid-cols-1 gap-3 lg:hidden">
                @forelse($selectedStatusItems as $item)
                    <div class="rounded-xl border border-border bg-white p-3 shadow-sm">
                        <div class="flex justify-between gap-2">
                            <div>
                                <p class="font-semibold">{{ $item->brand_name }}</p>
                                @if($item->campaign_name)
                                    <p class="text-xs text-muted-foreground">{{ $item->campaign_name }}</p>
                                @endif
                            </div>
                            <span class="rounded-full bg-muted px-2 py-1 text-xs text-foreground">
                                    {{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}
                                </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground mt-2">
                            <div>Platform<br><span class="text-foreground font-semibold">{{ \App\Models\Endorsement::PLATFORM_OPTIONS[$item->platform] ?? $item->platform }}</span></div>
                            <div>Posting<br><span class="text-foreground font-semibold">{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</span></div>
                            <div>Insight<br>
                                @if($item->insight_sent_at)
                                    <span class="text-emerald-600 font-semibold">Terkirim</span>
                                @elseif($item->insight_due_at)
                                    <span class="{{ $item->insight_due_at->isPast() ? 'text-red-600 font-semibold' : 'text-foreground' }}">
                                            {{ $item->insight_due_at->format('d/m/Y') }}
                                        </span>
                                @else
                                    -
                                @endif
                            </div>
                            <div>Payment<br><span class="text-foreground font-semibold">{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}</span></div>
                        </div>
                        <div class="mt-2 text-sm font-semibold {{ $item->net_profit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            Laba: Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-2">
                            <form method="POST" action="{{ route('endorsements.status.update', $item) }}" class="grid grid-cols-1 gap-2">
                                @csrf
                                <select name="status" class="form-select form-select-sm">
                                    @foreach($statusOptions as $key => $label)
                                        <option value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="inline-flex items-center justify-center rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90">Update Status</button>
                            </form>
                            <a href="{{ route('endorsements.show', $item) }}" class="inline-flex items-center justify-center rounded-md border border-border px-3 py-2 text-xs font-semibold text-foreground hover:bg-muted text-center">Detail</a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground">Tidak ada job di status ini.</p>
                @endforelse
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
                    <div id="tourSteps" class="space-y-3">
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

    {{-- Floating WhatsApp --}}
    <a href="https://wa.me/6285156637499?text=Halo%20kak%2C%20saya%20ingin%20bertanya%20tentang%20Endorse%20Tracker.%20Mohon%20info%20lebih%20lanjut."
       target="_blank" rel="noopener"
       class="fixed right-4 bottom-4 z-50">
        <div class="bg-[#25D366] text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg shadow-black/20 hover:scale-105 transition">
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
        if (steps.length) {
            let idx = 0;
            const showStep = () => {
                steps.forEach((el, i) => el.classList.toggle('d-none', i !== idx));
                const prev = document.getElementById('tourPrev');
                const next = document.getElementById('tourNext');
                if (prev && next) {
                    prev.disabled = idx === 0;
                    next.textContent = idx === steps.length - 1 ? 'Selesai' : 'Berikutnya';
                }
            };
            document.getElementById('tourPrev')?.addEventListener('click', () => { if (idx > 0) { idx--; showStep(); }});
            document.getElementById('tourNext')?.addEventListener('click', () => {
                if (idx < steps.length - 1) { idx++; showStep(); }
                else { bootstrap.Modal.getInstance(document.getElementById('tourModal'))?.hide(); }
            });
            showStep();
            const storageKey = 'endorse_tour_seen_v5';
            document.getElementById('tourModal')?.addEventListener('hidden.bs.modal', () => {
                localStorage.setItem(storageKey, '1');
            });
            if (!localStorage.getItem(storageKey)) {
                const autoTour = new bootstrap.Modal(document.getElementById('tourModal'));
                autoTour.show();
            }
        }

        // Chart income vs cost
        const ctx = document.getElementById('incomeChart');
        if (ctx) {
            const labels = @json($monthlyStats->pluck('month_key')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y')));
            const income = @json($monthlyStats->pluck('income')->map(fn($v) => (float) $v));
            const cost = @json($monthlyStats->pluck('cost')->map(fn($v) => (float) $v));
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Pendapatan',
                            data: income,
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false,
                            tension: 0.25,
                        },
                        {
                            label: 'Modal',
                            data: cost,
                            borderColor: 'rgb(148, 163, 184)',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false,
                            tension: 0.25,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { font: { size: 10 }, color: '#0f172a' } }
                    },
                    scales: {
                        x: { ticks: { color: '#475569' }, grid: { display: false } },
                        y: {
                            ticks: {
                                color: '#475569',
                                callback: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v)
                            },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        }
                    }
                }
            });
        }
    })();
</script>
@endpush
