@extends('layouts.app', ['title' => 'Login Endorse Tracker'])

@section('content')
    <div class="row justify-content-center pt-5">
        <div class="col-md-5 col-lg-4">
            <div class="card card-soft p-4">
                <h1 class="h4 fw-bold mb-2">Login</h1>
                <p class="text-muted-soft mb-4">Single user access untuk pencatatan endorse.</p>

                <form method="POST" action="{{ route('login.attempt') }}" class="d-grid gap-3">
                    @csrf
                    <div>
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autofocus>
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-dark">Masuk</button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('landing') }}" class="text-muted text-decoration-none">← Kembali ke beranda</a>
                </div>
            </div>
        </div>
    </div>
@endsection
