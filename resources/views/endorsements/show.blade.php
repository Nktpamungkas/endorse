@extends('layouts.app', ['title' => 'Detail Endorse'])

@section('content')
    <div class="page-head mb-3 align-items-start">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $endorsement->brand_name }}</h1>
            <div class="text-muted-soft">{{ $endorsement->campaign_name ?: 'Tanpa nama campaign' }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('endorsements.edit', $endorsement) }}" class="btn btn-outline-primary">Edit</a>
            <form method="POST" action="{{ route('endorsements.destroy', $endorsement) }}" onsubmit="return confirm('Hapus data endorse ini?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger">Hapus</button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card card-soft p-3 h-100">
                <h2 class="h6 fw-bold">Ringkasan Campaign</h2>
                <div class="row g-2 small mt-1">
                    <div class="col-md-6"><strong>Platform:</strong> {{ \App\Models\Endorsement::PLATFORM_OPTIONS[$endorsement->platform] ?? $endorsement->platform }}</div>
                    <div class="col-md-6"><strong>Jenis Konten:</strong> {{ \App\Models\Endorsement::CONTENT_TYPE_OPTIONS[$endorsement->content_type] ?? $endorsement->content_type }}</div>
                    <div class="col-md-6"><strong>Status:</strong> {{ \App\Models\Endorsement::STATUS_OPTIONS[$endorsement->status] ?? $endorsement->status }}</div>
                    <div class="col-md-6"><strong>Deal:</strong> {{ optional($endorsement->deal_date)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Order Produk:</strong> {{ optional($endorsement->product_ordered_at)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Produk Diterima:</strong> {{ optional($endorsement->product_received_at)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Posting Plan:</strong> {{ optional($endorsement->posting_date)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Sudah Posting:</strong> {{ optional($endorsement->posted_at)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Insight Due:</strong> {{ optional($endorsement->insight_due_at)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-md-6"><strong>Insight Terkirim:</strong> {{ optional($endorsement->insight_sent_at)->format('d/m/Y') ?? '-' }}</div>
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
                <h2 class="h6 fw-bold">Finansial</h2>
                <div class="small d-grid gap-2 mt-1">
                    <div><strong>Skema:</strong> {{ \App\Models\Endorsement::FINANCIAL_MODE_OPTIONS[$endorsement->financial_mode] ?? $endorsement->financial_mode }}</div>
                    <div><strong>Fee:</strong> Rp {{ number_format((float) $endorsement->fee_amount, 0, ',', '.') }}</div>
                    <div><strong>Reimburse:</strong> Rp {{ number_format((float) $endorsement->reimburse_amount, 0, ',', '.') }}</div>
                    <div><strong>Modal Produk:</strong> Rp {{ number_format((float) $endorsement->product_cost, 0, ',', '.') }}</div>
                    <div><strong>Biaya Lain:</strong> Rp {{ number_format((float) $endorsement->other_cost, 0, ',', '.') }}</div>
                    <div><strong>Pendapatan:</strong> Rp {{ number_format($endorsement->total_income, 0, ',', '.') }}</div>
                    <div><strong>Laba Bersih:</strong>
                        <span class="{{ $endorsement->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                            Rp {{ number_format($endorsement->net_profit, 0, ',', '.') }}
                        </span>
                    </div>
                    <div><strong>Payment:</strong> {{ \App\Models\Endorsement::PAYMENT_STATUS_OPTIONS[$endorsement->payment_status] ?? $endorsement->payment_status }}</div>
                    <div><strong>Due Payment:</strong> {{ optional($endorsement->payment_due_date)->format('d/m/Y') ?? '-' }}</div>
                    <div><strong>Payment Masuk:</strong> {{ optional($endorsement->payment_received_date)->format('d/m/Y') ?? '-' }}</div>
                    <div><strong>Beli sendiri:</strong> {{ $endorsement->self_purchase ? 'Ya' : 'Tidak' }}</div>
                    @if($endorsement->checkout_proof_path)
                        <div><strong>Bukti Checkout:</strong> <a href="{{ asset('storage/'.$endorsement->checkout_proof_path) }}" target="_blank">lihat file</a></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-soft p-3 mb-3">
        <h2 class="h6 fw-bold">Catatan</h2>
        <p class="mb-0">{{ $endorsement->notes ?: '-' }}</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card card-soft p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Tambah Histori Revisi</h2>
                <form method="POST" action="{{ route('endorsements.revisions.store', $endorsement) }}" class="row g-2">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Revisi</label>
                        <input type="date" name="revision_date" class="form-control" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end flex-wrap gap-2">
                        <div class="form-check me-3">
                            <input class="form-check-input" type="checkbox" name="uploaded_to_drive" value="1" id="uploaded_to_drive">
                            <label class="form-check-label" for="uploaded_to_drive">Sudah di Drive</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_approved" value="1" id="is_approved">
                            <label class="form-check-label" for="is_approved">Approved</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan Revisi</label>
                        <textarea name="note" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-dark">Simpan Revisi</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-soft p-3 h-100">
                <h2 class="h6 fw-bold mb-3">Daftar Revisi</h2>
                <div class="d-grid gap-2">
                    @forelse($endorsement->revisions as $rev)
                        <div class="border rounded-3 p-2">
                            <div class="d-flex justify-content-between">
                                <div class="fw-semibold">{{ $rev->revision_date->format('d/m/Y') }}</div>
                                <form method="POST" action="{{ route('endorsements.revisions.destroy', [$endorsement, $rev]) }}" onsubmit="return confirm('Hapus revisi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger p-0">hapus</button>
                                </form>
                            </div>
                            <div class="small text-muted-soft">
                                {{ $rev->uploaded_to_drive ? 'Upload Drive: Ya' : 'Upload Drive: Tidak' }} |
                                {{ $rev->is_approved ? 'Approved' : 'Belum approved' }}
                            </div>
                            <div class="small mt-1">{{ $rev->note ?: '-' }}</div>
                        </div>
                    @empty
                        <div class="text-muted-soft small">Belum ada histori revisi.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
