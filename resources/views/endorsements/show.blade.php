@extends('layouts.app', ['title' => 'Detail Endorse'])

@section('content')
    <div class="page-head mb-3 align-items-start">
        <div>
            <h1 class="h3 fw-bold mb-1">
                {{ $endorsement->brand_name }}
                @if($endorsement->trashed())
                    <span class="badge bg-danger ms-1 align-middle">Dibatalkan</span>
                @endif
            </h1>
            <div class="text-muted-soft">{{ $endorsement->campaign_name ?: 'Tanpa nama campaign' }}</div>
        </div>
        @if(! $endorsement->trashed())
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('endorsements.edit', $endorsement) }}" class="btn btn-outline-primary">Ubah data</a>
                <form method="POST" action="{{ route('endorsements.destroy', $endorsement) }}" id="deleteForm">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="delete_reason" id="delete_reason">
                    <button type="button" class="btn btn-outline-danger" id="triggerDelete">Hapus data</button>
                </form>
            </div>
        @endif
    </div>

    @if($endorsement->trashed())
        <div class="alert alert-warning border-0 shadow-sm">
            <div class="fw-semibold mb-1">Data ini sudah dibatalkan.</div>
            <div>Alasan: <strong>{{ $endorsement->deleted_reason ?: '-' }}</strong></div>
            <div>Dihapus pada: {{ optional($endorsement->deleted_at)->format('d/m/Y H:i') ?? '-' }}</div>
            <div>Dihapus oleh: {{ optional($endorsement->deletedBy)->username ?? '-' }}</div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card card-soft p-3 h-100">
                <h2 class="h6 fw-bold mb-1">Ringkasan campaign</h2>
                <p class="field-hint mb-3">Informasi utama yang paling sering dicari saat mengecek pekerjaan.</p>
                <div class="row g-2 small mt-1">
                    <div class="col-md-6"><strong>Platform:</strong> {{ \App\Models\Endorsement::PLATFORM_OPTIONS[$endorsement->platform] ?? $endorsement->platform }}</div>
                    <div class="col-md-6"><strong>Jenis konten:</strong> {{ \App\Models\Endorsement::CONTENT_TYPE_OPTIONS[$endorsement->content_type] ?? $endorsement->content_type }}</div>
                    <div class="col-md-6"><strong>Status:</strong> <span class="badge-soft is-neutral ms-1">{{ \App\Models\Endorsement::STATUS_OPTIONS[$endorsement->status] ?? $endorsement->status }}</span></div>
                    <div class="col-md-6"><strong>Deal:</strong> {{ optional($endorsement->deal_date)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Order produk:</strong> {{ optional($endorsement->product_ordered_at)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Produk diterima:</strong> {{ optional($endorsement->product_received_at)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Rencana posting:</strong> {{ optional($endorsement->posting_date)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Sudah posting:</strong> {{ optional($endorsement->posted_at)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Batas insight:</strong> {{ optional($endorsement->insight_due_at)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Insight terkirim:</strong> {{ optional($endorsement->insight_sent_at)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Upload Drive:</strong> {{ $endorsement->drive_uploaded ? 'Sudah' : 'Belum' }}</div>
                    <div class="col-md-6"><strong>Storyline:</strong> {{ $endorsement->storyline_required ? ($endorsement->storyline_done ? 'Perlu, sudah selesai' : 'Perlu, belum selesai') : 'Tidak perlu' }}</div>
                    <div class="col-md-6"><strong>Boostcode:</strong>
                        @if($endorsement->boostcode_required)
                            {{ $endorsement->boostcode_duration_days }} hari
                            @if($endorsement->boostcode_deadline)
                                (sampai {{ $endorsement->boostcode_deadline->format('d/m/Y') }})
                            @endif
                        @else
                            Tidak diminta
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-soft p-3 h-100">
                <h2 class="h6 fw-bold mb-1">Finansial</h2>
                <p class="field-hint mb-3">Bagian ini menunjukkan uang masuk, uang keluar, dan hasil akhirnya.</p>
                <div class="small d-grid gap-2 mt-1">
                    <div><strong>Skema:</strong> {{ \App\Models\Endorsement::FINANCIAL_MODE_OPTIONS[$endorsement->financial_mode] ?? $endorsement->financial_mode }}</div>
                    <div><strong>Fee:</strong> Rp {{ number_format((float) $endorsement->fee_amount, 0, ',', '.') }}</div>
                    <div><strong>Reimburse:</strong> Rp {{ number_format((float) $endorsement->reimburse_amount, 0, ',', '.') }}</div>
                    <div><strong>Modal produk:</strong> Rp {{ number_format((float) $endorsement->product_cost, 0, ',', '.') }}</div>
                    <div><strong>Biaya lain:</strong> Rp {{ number_format((float) $endorsement->other_cost, 0, ',', '.') }}</div>
                    <div><strong>Pendapatan:</strong> Rp {{ number_format($endorsement->total_income, 0, ',', '.') }}</div>
                    <div><strong>Laba bersih:</strong>
                        <span class="{{ $endorsement->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($endorsement->net_profit, 0, ',', '.') }}
                        </span>
                    </div>
                    <div><strong>Payment:</strong> <span class="badge-soft is-neutral">{{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$endorsement->payment_status] ?? $endorsement->payment_status }}</span></div>
                    <div><strong>Jatuh tempo payment:</strong> {{ optional($endorsement->payment_due_date)->format('d/m/Y') ?? '-' }}</div>
                    <div><strong>Payment masuk:</strong> {{ optional($endorsement->payment_received_date)->format('d/m/Y') ?? '-' }}</div>
                    <div><strong>Beli sendiri:</strong> {{ $endorsement->self_purchase ? 'Ya' : 'Tidak' }}</div>
                    @if($endorsement->checkout_proof_path)
                        <div><strong>Bukti checkout:</strong> <a href="{{ asset('storage/'.$endorsement->checkout_proof_path) }}" target="_blank">Lihat file</a></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-soft p-3 mb-3">
        <h2 class="h6 fw-bold mb-1">Catatan</h2>
        <p class="mb-0">{{ $endorsement->notes ?: 'Tidak ada catatan tambahan.' }}</p>
    </div>

    <div class="row g-3">
        @if(! $endorsement->trashed())
            <div class="col-lg-6">
                <div class="card card-soft p-3 h-100">
                    <h2 class="h6 fw-bold mb-1">Tambah histori revisi</h2>
                    <p class="field-hint mb-3">Gunakan ini untuk mencatat perubahan draft atau approval.</p>
                    <form method="POST" action="{{ route('endorsements.revisions.store', $endorsement) }}" class="row g-2">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Tanggal revisi</label>
                            <input type="date" name="revision_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end flex-wrap gap-2">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="uploaded_to_drive" value="1" id="uploaded_to_drive">
                                <label class="form-check-label" for="uploaded_to_drive">Sudah masuk Drive</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_approved" value="1" id="is_approved">
                                <label class="form-check-label" for="is_approved">Sudah disetujui</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan revisi</label>
                            <textarea name="note" rows="3" class="form-control" placeholder="Tulis singkat perubahan yang dilakukan"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-dark">Simpan revisi</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="col-lg-6">
                <div class="card card-soft p-3 h-100">
                    <h2 class="h6 fw-bold mb-1">Tambah histori revisi</h2>
                    <div class="text-muted-soft small">Data sudah dibatalkan, jadi revisi baru tidak bisa ditambahkan.</div>
                </div>
            </div>
        @endif
        <div class="col-lg-6">
            <div class="card card-soft p-3 h-100">
                <h2 class="h6 fw-bold mb-1">Daftar revisi</h2>
                <p class="field-hint mb-3">Semua revisi terbaru ditampilkan di bawah ini.</p>
                <div class="d-grid gap-2">
                    @forelse($endorsement->revisions as $rev)
                        <div class="border rounded-3 p-2">
                            <div class="d-flex justify-content-between gap-2">
                                <div class="fw-semibold">{{ $rev->revision_date->format('d/m/Y') }}</div>
                                @if(! $endorsement->trashed())
                                    <form method="POST" action="{{ route('endorsements.revisions.destroy', [$endorsement, $rev]) }}" onsubmit="return confirm('Hapus revisi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-link text-danger p-0">Hapus</button>
                                    </form>
                                @endif
                            </div>
                            <div class="small text-muted-soft">
                                {{ $rev->uploaded_to_drive ? 'Sudah masuk Drive' : 'Belum masuk Drive' }} |
                                {{ $rev->is_approved ? 'Sudah disetujui' : 'Belum disetujui' }}
                            </div>
                            <div class="small mt-1">{{ $rev->note ?: '-' }}</div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <div class="empty-state-title">Belum ada histori revisi</div>
                            <p class="empty-state-text">Tambahkan revisi pertama jika ada perubahan draft.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card card-soft p-3 mt-3">
        <h2 class="h6 fw-bold mb-1">Log aktivitas</h2>
        <p class="field-hint mb-3">Riwayat ini membantu Anda melihat perubahan penting pada campaign.</p>
        <div class="d-grid gap-2 small">
            @php $logs = $endorsement->activities()->limit(15)->get(); @endphp
            @forelse($logs as $log)
                <div class="border rounded-3 p-2 d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold">{{ str_replace('_', ' ', $log->action) }}</div>
                        @if($log->meta)
                            <div class="text-muted small">
                                @foreach($log->meta as $k => $v)
                                    @if($k === 'changes' && is_array($v))
                                        <div class="mt-1">Perubahan:</div>
                                        <ul class="mb-1 ps-3">
                                            @foreach($v as $field => $change)
                                                <li>{{ $field }}: <strong>{{ $change['from'] === null || $change['from'] === '' ? '-' : $change['from'] }}</strong> -> <strong>{{ $change['to'] === null || $change['to'] === '' ? '-' : $change['to'] }}</strong></li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="me-2">{{ $k }}: <strong>{{ is_array($v) ? json_encode($v) : $v }}</strong></div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-state-title">Belum ada aktivitas</div>
                    <p class="empty-state-text">Setelah data diubah, catatan aktivitas akan muncul di sini.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const trigger = document.getElementById('triggerDelete');
    const form = document.getElementById('deleteForm');
    const reasonInput = document.getElementById('delete_reason');
    if (!trigger || !form || !reasonInput) return;

    trigger.addEventListener('click', () => {
        const reason = prompt('Tuliskan alasan data ini dibatalkan:');
        if (!reason || !reason.trim()) {
            return;
        }
        reasonInput.value = reason.trim().slice(0, 500);
        form.submit();
    });
})();
</script>
@endpush
