@extends('layouts.app', ['title' => 'Dashboard Endorse'])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold text-foreground">Dashboard</h1>
                <p class="text-sm text-muted-foreground">Lihat ringkasan cepat supaya pekerjaan endorse lebih mudah dipantau.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                        data-bs-toggle="modal" data-bs-target="#tourModal">
                    Panduan singkat
                </button>
                <a href="{{ route('endorsements.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition">
                    Tambah data endorse
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-2">
            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <p class="text-xs text-muted-foreground">Laba bersih</p>
                <p class="text-2xl font-semibold {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($netProfit, 0, ',', '.') }}</p>
                <p class="text-xs text-muted-foreground mt-1">Hasil akhir dari semua endorse yang sudah masuk.</p>
                <p class="text-xs text-muted-foreground mt-1">Sudah diterima: Rp {{ number_format($receivedNetProfit, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <p class="text-xs text-muted-foreground">Total pendapatan</p>
                <p class="text-2xl font-semibold">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
                <p class="text-xs text-muted-foreground mt-1">Uang yang didapat dari fee, reimburse, atau skema lain.</p>
            </div>
            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <p class="text-xs text-muted-foreground">Total modal</p>
                <p class="text-2xl font-semibold">Rp {{ number_format($totalCost, 0, ',', '.') }}</p>
                <p class="text-xs text-muted-foreground mt-1">Biaya yang sudah keluar untuk produk dan keperluan lain.</p>
            </div>
            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <p class="text-xs text-muted-foreground">Menunggu payment</p>
                <p class="text-2xl font-semibold">{{ $waitingPayment }} endorse</p>
                <p class="text-xs text-muted-foreground mt-1">Perlu ditagih atau dicek lagi pembayarannya.</p>
            </div>
        </div>

        <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="text-xs text-muted-foreground">Tren bulanan</p>
                    <h2 class="text-sm font-semibold text-foreground">Pendapatan vs modal</h2>
                </div>
            </div>
            <div class="h-36">
                <canvas id="incomeChart" height="90"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 rounded-xl border border-border bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Status endorse</h2>
                        <p class="text-xs text-muted-foreground">Klik kartu untuk melihat isi per status.</p>
                    </div>
                    <span class="text-xs text-muted-foreground">Ringkasan cepat</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($statusOptions as $key => $label)
                        <a href="{{ route('dashboard', ['status_view' => $key]) }}"
                           class="flex items-center justify-between rounded-lg border border-border px-3 py-2 hover:border-primary/60 hover:bg-muted/60 transition {{ $selectedStatus === $key ? 'bg-primary/5 ring-1 ring-primary/40' : '' }}">
                            <span>
                                <span class="block font-medium text-sm">{{ $label }}</span>
                                <span class="block text-xs text-muted-foreground">Klik untuk lihat daftar</span>
                            </span>
                            <span class="inline-flex items-center justify-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedStatus === $key ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground' }}">
                                {{ $statusCounts[$key] ?? 0 }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-border bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-sm font-semibold text-foreground">Payment belum lunas</h2>
                        <p class="text-xs text-muted-foreground">Daftar yang masih perlu ditagih.</p>
                    </div>
                    <a href="{{ route('endorsements.index', ['status' => 'menunggu_payment']) }}"
                       class="text-xs font-semibold text-primary hover:underline">Buka daftar</a>
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
                                    {{ optional($item->payment_due_date)->format('d/m/Y') ?? 'Belum ada tanggal' }}
                                </span>
                            </div>
                            <div class="mt-2 inline-flex flex-wrap items-center gap-2 text-xs">
                                <span class="badge-soft is-warning">
                                    {{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}
                                </span>
                                <span class="badge-soft is-neutral">
                                    Rp {{ number_format($item->total_cost, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <div class="empty-state-title">Tidak ada payment yang tertunda</div>
                            <p class="empty-state-text">Semua data yang tampil di sini sudah lunas.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-border bg-white p-4 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-muted-foreground uppercase tracking-[0.15em]">Detail status</p>
                    <h3 class="text-lg font-semibold">{{ $statusOptions[$selectedStatus] ?? $selectedStatus }}</h3>
                </div>
                <a href="{{ route('endorsements.index', ['status' => $selectedStatus]) }}"
                   class="text-sm font-semibold text-primary hover:underline">Lihat semua data</a>
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
                            <th class="py-2 text-right">Laba bersih</th>
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
                                        <span class="badge-soft is-success">Sudah dikirim</span>
                                    @elseif($item->insight_due_at)
                                        <span class="badge-soft {{ $item->insight_due_at->isPast() ? 'is-danger' : 'is-info' }}">
                                            {{ $item->insight_due_at->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="badge-soft is-neutral">Tidak ada</span>
                                    @endif
                                </td>
                                <td class="py-2">
                                    <span class="badge-soft is-neutral">
                                        {{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}
                                    </span>
                                </td>
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
                                        <button class="inline-flex items-center justify-center rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90">Ubah status</button>
                                        <a href="{{ route('endorsements.show', $item) }}" class="inline-flex items-center justify-center rounded-md border border-border px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-muted">Lihat detail</a>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-5">
                                    <div class="empty-state">
                                        <div class="empty-state-title">Belum ada data di status ini</div>
                                        <p class="empty-state-text">Coba pilih status lain atau tambahkan data endorse baru.</p>
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
            </div>

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
                            <span class="badge-soft is-neutral">
                                {{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$item->payment_status] ?? $item->payment_status }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground mt-2">
                            <div>Platform<br><span class="text-foreground font-semibold">{{ \App\Models\Endorsement::PLATFORM_OPTIONS[$item->platform] ?? $item->platform }}</span></div>
                            <div>Posting<br><span class="text-foreground font-semibold">{{ optional($item->posting_date)->format('d/m/Y') ?? '-' }}</span></div>
                            <div>Insight<br>
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
                                <button class="inline-flex items-center justify-center rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90">Ubah status</button>
                            </form>
                            <a href="{{ route('endorsements.show', $item) }}" class="inline-flex items-center justify-center rounded-md border border-border px-3 py-2 text-xs font-semibold text-foreground hover:bg-muted text-center">Lihat detail</a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-title">Belum ada data di status ini</div>
                        <p class="empty-state-text">Coba pilih status lain atau tambahkan data endorse baru.</p>
                        <div class="mt-3">
                            <a href="{{ route('endorsements.create') }}" class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground">Tambah data endorse</a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="modal fade" id="tourModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Panduan singkat Endorse Tracker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="tourSteps" class="space-y-3">
                        <div class="tour-step">
                            <h6 class="fw-bold">1. Lihat ringkasan</h6>
                            <p class="text-muted mb-0">Dashboard menampilkan kondisi endorse, status pembayaran, dan laba secara singkat.</p>
                        </div>
                        <div class="tour-step d-none">
                            <h6 class="fw-bold">2. Tambah data baru</h6>
                            <p class="text-muted mb-0">Klik <strong>Tambah data endorse</strong>, lalu isi nama brand, platform, dan informasi penting lainnya.</p>
                        </div>
                        <div class="tour-step d-none">
                            <h6 class="fw-bold">3. Pindahkan status</h6>
                            <p class="text-muted mb-0">Gunakan tombol <strong>Ubah status</strong> untuk menandai progres pekerjaan.</p>
                        </div>
                        <div class="tour-step d-none">
                            <h6 class="fw-bold">4. Cek payment</h6>
                            <p class="text-muted mb-0">Bagian payment belum lunas membantu Anda mengetahui data mana yang masih perlu ditagih.</p>
                        </div>
                        <div class="tour-step d-none">
                            <h6 class="fw-bold">5. Buka detail</h6>
                            <p class="text-muted mb-0">Halaman detail berisi informasi lengkap, revisi, dan catatan kerja.</p>
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
