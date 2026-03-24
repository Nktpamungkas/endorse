@extends('layouts.app', ['title' => 'Login Endorse Tracker'])

@section('content')
    <div class="min-vh-100 d-flex align-items-center justify-content-center py-5"
         style="background: radial-gradient(circle at 20% 20%, #e8f0ff 0, transparent 35%), radial-gradient(circle at 80% 10%, #ffe7d1 0, transparent 35%), linear-gradient(135deg, #f8fbff, #fff8f1);">
        <div class="w-100" style="max-width: 460px;">
            <div class="rounded-3 border border-1 border-light shadow-sm p-4 p-md-5 bg-white bg-opacity-90"
                 style="backdrop-filter: blur(6px);">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="text-uppercase text-muted small mb-1">Endorse Tracker</p>
                        <h1 class="h4 fw-bold mb-0">Masuk</h1>
                        <p class="text-muted mb-0">Gunakan akun single-user untuk mulai kelola endorse.</p>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-3 py-2 rounded-pill">Shadcn UI</span>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <div class="fw-semibold mb-1">Ada yang perlu dicek:</div>
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="d-grid gap-3">
                    @csrf
                    <div class="d-grid gap-2">
                        <label class="fw-semibold small text-muted" for="username">Username</label>
                        <input id="username" type="text" name="username"
                               value="{{ old('username') }}"
                               required autofocus
                               class="form-control border border-1 border-secondary-subtle rounded-3 py-2 px-3 shadow-sm"
                               style="border-color: hsl(var(--input)); box-shadow: none;">
                    </div>

                    <div class="d-grid gap-2">
                        <label class="fw-semibold small text-muted" for="password">Password</label>
                        <input id="password" type="password" name="password" required
                               class="form-control border border-1 border-secondary-subtle rounded-3 py-2 px-3 shadow-sm"
                               style="border-color: hsl(var(--input)); box-shadow: none;">
                    </div>

                    <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2 py-2 rounded-3 fw-semibold"
                            style="background: linear-gradient(120deg, hsl(var(--primary)) 0%, hsl(var(--primary)) 60%, hsl(var(--accent)) 120%); border: none;">
                        Masuk
                    </button>
                </form>

                <div class="d-flex align-items-center gap-2 mt-4 text-muted small">
                    <span class="opacity-75">Tip:</span>
                    <span class="fw-semibold">Username/Password: dhedhepratiwi</span>
                </div>

                <div class="text-center mt-3">
                    <a href="{{ route('landing') }}" class="text-decoration-none" style="color: hsl(var(--primary));">
                        ← Kembali ke beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
