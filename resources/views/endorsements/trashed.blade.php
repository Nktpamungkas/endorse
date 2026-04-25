@extends('layouts.app', ['title' => 'Endorse Dihapus'])

@section('content')
    <div class="page-head mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Arsip hapus</h1>
            <div class="text-muted-soft">Lihat data yang pernah dibatalkan beserta alasannya.</div>
        </div>
        <a href="{{ route('endorsements.index') }}" class="btn btn-outline-dark">Kembali ke data</a>
    </div>

    <div class="card card-soft p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small text-muted-soft">Cari brand atau campaign</label>
                <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="contoh: Wardah">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100">Terapkan filter</button>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="{{ route('endorsements.trashed') }}" class="btn btn-outline-secondary w-100">Hapus filter</a>
            </div>
        </form>
    </div>

    <div class="card card-soft p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Brand</th>
                    <th>Status Terakhir</th>
                    <th>Dihapus</th>
                    <th>Alasan</th>
                    <th>Dihapus oleh</th>
                    <th class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($endorsements as $item)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $item->brand_name }}</div>
                            @if($item->campaign_name)
                                <div class="small text-muted-soft">{{ $item->campaign_name }}</div>
                            @endif
                        </td>
                        <td><span class="badge-soft is-neutral">{{ \App\Models\Endorsement::STATUS_OPTIONS[$item->status] ?? $item->status }}</span></td>
                        <td class="cell-nowrap">{{ optional($item->deleted_at)->format('d/m/Y H:i') }}</td>
                        <td class="small">{{ $item->deleted_reason ?: '-' }}</td>
                        <td class="cell-nowrap">{{ optional($item->deletedBy)->username ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('endorsements.trashed.show', $item->id) }}" class="btn btn-sm btn-outline-dark">Lihat detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-5">
                            <div class="empty-state">
                                <div class="empty-state-title">Belum ada data di arsip hapus</div>
                                <p class="empty-state-text">Saat ada endorse yang dibatalkan, datanya akan tampil di sini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $endorsements->links() }}
    </div>
@endsection
