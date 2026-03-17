@extends('layouts.app', ['title' => 'Kelola User'])

@section('content')
    <div class="page-head mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Kelola User Trial</h1>
            <div class="text-muted-soft">Buat, perpanjang, atau nonaktifkan akun trial.</div>
        </div>
    </div>

    <div class="card card-soft p-3 mb-3">
        <h2 class="h6 fw-bold mb-3">Tambah User Trial</h2>
        <form method="POST" action="{{ route('users.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Password</label>
                <input type="text" name="password" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Trial berakhir</label>
                <input type="date" name="trial_ends_at" class="form-control">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-dark w-100">Buat User Trial</button>
            </div>
        </form>
    </div>

    <div class="card card-soft p-3">
        <h2 class="h6 fw-bold mb-3">Daftar User</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Trial Berakhir</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->username }}</td>
                        <td>{{ ucfirst($user->role) }}</td>
                    <td>{{ $user->trial_ends_at ? \Illuminate\Support\Carbon::parse($user->trial_ends_at)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <span class="badge {{ $user->active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $user->active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                        <td class="text-end">
                            @if($user->role === 'master')
                                <span class="text-muted small">Master</span>
                            @else
                                <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-end">
                                    <form method="POST" action="{{ route('users.update', $user) }}" class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-end">
                                        @csrf
                                        <input type="date" name="trial_ends_at" value="{{ $user->trial_ends_at }}" class="form-control form-control-sm" style="max-width: 160px">
                                        <select name="active" class="form-select form-select-sm" style="max-width:120px">
                                            <option value="1" @selected($user->active)>Aktif</option>
                                            <option value="0" @selected(!$user->active)>Nonaktif</option>
                                        </select>
                                        <input type="text" name="password" class="form-control form-control-sm" placeholder="Reset password (opsional)" style="max-width: 200px">
                                        <button class="btn btn-sm btn-dark">Update</button>
                                    </form>
                                    <form method="POST" action="{{ route('users.forceLogout', $user) }}" class="d-inline" onsubmit="return confirm('Paksa logout user ini?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning">Force Logout</button>
                                    </form>
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Hapus user ini beserta semua datanya?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Hapus + Data</button>
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
