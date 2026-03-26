@extends('layouts.app', ['title' => 'Login Endorse Tracker'])

@section('content')
    <div class="login-shell d-flex min-vh-100 align-items-center justify-content-center">
        <div class="login-wrap w-100">
            <div class="login-card border border-1 border-light bg-white bg-opacity-90 p-4 p-md-5 shadow-sm">
                <div class="mb-3 d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <p class="mb-1 text-uppercase text-muted small">Endorse Tracker</p>
                        <h1 class="mb-0 h4 fw-bold">Masuk</h1>
                        <p class="mb-0 text-muted">Gunakan akun single-user untuk mulai kelola endorse.</p>
                    </div>
                    <span class="login-badge badge rounded-pill bg-primary bg-opacity-10 px-3 py-2 fw-semibold text-primary">
                        Shadcn UI
                    </span>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <div class="mb-1 fw-semibold">Ada yang perlu dicek:</div>
                        <ul class="mb-0 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="d-grid gap-3" novalidate>
                    @csrf
                    <div class="d-grid gap-2">
                        <label class="fw-semibold small text-muted" for="username">Username</label>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            autocomplete="username"
                            autocapitalize="none"
                            autocorrect="off"
                            spellcheck="false"
                            inputmode="text"
                            enterkeyhint="next"
                            class="login-field form-control rounded-3 border border-1 border-secondary-subtle px-3 py-2 shadow-sm"
                            style="border-color: hsl(var(--input)); box-shadow: none;"
                        >
                    </div>

                    <div class="d-grid gap-2">
                        <label class="fw-semibold small text-muted" for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            autocapitalize="none"
                            autocorrect="off"
                            spellcheck="false"
                            enterkeyhint="go"
                            class="login-field form-control rounded-3 border border-1 border-secondary-subtle px-3 py-2 shadow-sm"
                            style="border-color: hsl(var(--input)); box-shadow: none;"
                        >
                    </div>

                    <button
                        class="login-button btn btn-primary d-flex align-items-center justify-content-center gap-2 rounded-3 py-2 fw-semibold"
                        style="background: linear-gradient(120deg, hsl(var(--primary)) 0%, hsl(var(--primary)) 60%, hsl(var(--accent)) 120%); border: none;"
                    >
                        Masuk
                    </button>
                </form>

                <div class="login-help mt-4 d-flex align-items-center gap-2 text-muted small">
                    <span class="opacity-75">Tip:</span>
                    <span class="fw-semibold">Username/Password: dhedhepratiwi</span>
                </div>

                <div class="mt-3 text-center">
                    <a href="{{ route('landing') }}" class="text-decoration-none" style="color: hsl(var(--primary));">
                        &larr; Kembali ke beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
