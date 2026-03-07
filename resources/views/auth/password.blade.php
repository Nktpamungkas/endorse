@extends('layouts.app', ['title' => 'Ganti Password'])

@section('content')
    <div class="row justify-content-center pt-4">
        <div class="col-md-5 col-lg-4">
            <div class="card card-soft p-4">
                <h1 class="h5 fw-bold mb-3">Ganti Password</h1>
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form method="POST" action="{{ route('password.update') }}" class="d-grid gap-3">
                    @csrf
                    <div>
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button class="btn btn-dark">Simpan</button>
                </form>
            </div>
        </div>
    </div>
@endsection
