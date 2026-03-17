@extends('layouts.app', ['title' => 'Kelola User'])

@section('content')
    <div class="page-head mb-3 align-items-start">
        <div>
            <div class="text-muted-soft small mb-1">Settings · User & Access</div>
            <h1 class="h3 fw-bold mb-1">Kelola User</h1>
            <div class="text-muted-soft">Tambah akun, atur role, pantau sesi aktif, dan paksa logout bila diperlukan.</div>
        </div>
        <div class="text-end text-muted-soft small">
            Sesi trial 2 jam · Berlangganan 8 jam · Master 8 jam
        </div>
    </div>

    @php
        $totalUsers = $users->count();
        $trialCount = $users->where('role', 'trial')->count();
        $paidCount = $users->where('role', 'paid')->count();
        $onlineCount = $onlineUserIds->count();
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card card-soft p-3">
                <div class="text-muted-soft small">Total User</div>
                <div class="h5 fw-bold mb-0">{{ $totalUsers }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card card-soft p-3">
                <div class="text-muted-soft small">Trial</div>
                <div class="h5 fw-bold mb-0">{{ $trialCount }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card card-soft p-3">
                <div class="text-muted-soft small">Berlangganan</div>
                <div class="h5 fw-bold mb-0">{{ $paidCount }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card card-soft p-3">
                <div class="text-muted-soft small">Online (15 menit)</div>
                <div class="h5 fw-bold mb-0">{{ $onlineCount }}</div>
            </div>
        </div>
    </div>

    <div class="card card-soft p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="text-muted-soft small">Quick action</div>
                <h2 class="h6 fw-bold mb-0">Tambah User</h2>
            </div>
            <span class="text-muted small">Isi singkat · Klik Buat</span>
        </div>
        <form method="POST" action="{{ route('users.store') }}" class="row g-3 align-items-start" id="createUserForm">
            @csrf
            <div class="col-lg-3 col-md-6">
                <label class="form-label small text-muted-soft">Username</label>
                <input type="text" name="username" class="form-control" required placeholder="mis. johndoe">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label small text-muted-soft d-flex justify-content-between">
                    <span>Password</span>
                    <span class="text-muted">berikan ke user</span>
                </label>
                <input type="text" name="password" class="form-control" required placeholder="min 4 karakter">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label small text-muted-soft">Role</label>
                <select name="role" class="form-select" data-role-select>
                    <option value="trial" selected>Trial</option>
                    <option value="paid">Berlangganan</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label small text-muted-soft">Trial Berakhir</label>
                <input type="date" name="trial_ends_at" class="form-control" data-trial-date>
                <div class="form-text small">Kosongkan jika berlangganan.</div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-dark px-4">Buat User</button>
            </div>
        </form>
    </div>

    <div class="card card-soft p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <div class="text-muted-soft small">Filter & Pencarian</div>
                <h2 class="h6 fw-bold mb-0">Cari user cepat</h2>
            </div>
        </div>
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5 col-12">
                <label class="form-label small text-muted-soft">Username</label>
                <input type="text" name="q" value="{{ $query['q'] ?? '' }}" class="form-control" placeholder="mis. dhedhe">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small text-muted-soft">Role</label>
                <select name="role" class="form-select">
                    <option value="">Semua</option>
                    <option value="trial" @selected(($query['role'] ?? '') === 'trial')>Trial</option>
                    <option value="paid" @selected(($query['role'] ?? '') === 'paid')>Berlangganan</option>
                    <option value="master" @selected(($query['role'] ?? '') === 'master')>Master</option>
                </select>
            </div>
            <div class="col-md-2 col-6 d-grid">
                <button class="btn btn-dark">Terapkan</button>
            </div>
            <div class="col-md-2 col-6 d-grid">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card card-soft p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="text-muted-soft small">Users</div>
                <h2 class="h6 fw-bold mb-0">Daftar User</h2>
            </div>
            <span class="text-muted small">Edit inline · Logout paksa · Hapus</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Trial Berakhir</th>
                    <th>Status</th>
                    <th>Online</th>
                    <th class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->username }}</td>
                    <td>{{ $user->role === 'paid' ? 'Berlangganan' : ucfirst($user->role) }}</td>
                    <td>{{ $user->trial_ends_at ? \Illuminate\Support\Carbon::parse($user->trial_ends_at)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <span class="badge {{ $user->active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $user->active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td>
                        @php $isOnline = $onlineUserIds->contains($user->id); @endphp
                        <span class="badge {{ $isOnline ? 'bg-success' : 'bg-secondary' }}">
                            {{ $isOnline ? 'Online' : 'Offline' }}
                        </span>
                    </td>
                        <td class="text-end">
                            @if($user->role === 'master')
                                <span class="text-muted small">Master</span>
                            @else
                                <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-end user-row-form">
                                    <form method="POST" action="{{ route('users.update', $user) }}" class="d-flex flex-wrap align-items-center gap-2">
                                        @csrf
                                        <input type="date" name="trial_ends_at" value="{{ $user->trial_ends_at }}" class="form-control form-control-sm" style="max-width: 140px" data-trial-date>
                                        <select name="role" class="form-select form-select-sm" style="max-width:130px" data-role-select @disabled($user->role === 'master')>
                                            <option value="trial" @selected($user->role === 'trial')>Trial</option>
                                            <option value="paid" @selected($user->role === 'paid')>Berlangganan</option>
                                            <option value="master" @selected($user->role === 'master')>Master</option>
                                        </select>
                                        <select name="active" class="form-select form-select-sm" style="max-width:110px">
                                            <option value="1" @selected($user->active)>Aktif</option>
                                            <option value="0" @selected(!$user->active)>Nonaktif</option>
                                        </select>
                                        <input type="text" name="password" class="form-control form-control-sm" placeholder="Reset password (opsional)" style="max-width: 170px">
                                        <button class="btn btn-sm btn-dark">Simpan</button>
                                    </form>
                                    <form method="POST" action="{{ route('users.forceLogout', $user) }}" class="d-inline" onsubmit="return confirm('Paksa logout user ini?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning">Logout</button>
                                    </form>
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Hapus user ini beserta semua datanya?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const toggleTrialDate = (roleSelect, dateInput) => {
        if (!roleSelect || !dateInput) return;
        const trial = roleSelect.value === 'trial';
        dateInput.disabled = !trial;
        if (!trial) dateInput.value = '';
    };

    document.querySelectorAll('[data-role-select]').forEach(select => {
        const row = select.closest('form');
        const dateInput = row ? row.querySelector('[data-trial-date]') : null;
        toggleTrialDate(select, dateInput);
        select.addEventListener('change', () => toggleTrialDate(select, dateInput));
    });
</script>
@endpush
