<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Endorse Tracker' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @stack('head')
</head>
<body>
<div class="app-bg">
    @if(auth()->check())
        {{-- Mobile top bar --}}
        <div class="lg:hidden bg-white/90 backdrop-blur border-b border-border sticky top-0 z-40">
            <div class="px-4 py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button id="mobileMenuBtn" aria-label="Buka menu" class="inline-flex items-center justify-center rounded-lg border border-border px-3 py-2 text-sm font-semibold text-foreground">
                        Menu
                    </button>
                    <div>
                        <div class="text-sm font-semibold text-foreground">Endorse Tracker</div>
                        <div class="text-xs text-muted-foreground">Lihat ringkasan pekerjaan</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-foreground">Beranda</a>
                    <a href="{{ route('endorsements.create') }}" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground">Tambah</a>
                </div>
            </div>
        </div>

        {{-- Mobile menu overlay --}}
        <div id="mobileMenuPanel" class="mobile-menu hidden lg:hidden">
            <div class="mobile-menu-overlay" id="mobileMenuClose"></div>
            <div class="mobile-menu-sheet">
                <div class="px-4 py-4 border-b border-border flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-foreground">Endorse Tracker</div>
                        <div class="text-xs text-muted-foreground">Lihat ringkasan pekerjaan</div>
                    </div>
                    <button id="mobileMenuX" aria-label="Tutup menu" class="rounded-lg border border-border px-3 py-1 text-sm font-semibold text-foreground">Tutup</button>
                </div>
                <nav class="p-3 space-y-4 text-sm">
                    <div class="space-y-1">
                        <p class="px-2 text-xs uppercase tracking-[0.12em] text-muted-foreground">Home</p>
                        <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Beranda</a>
                        <a class="sidebar-link {{ request()->routeIs('endorsements.*') && !request()->routeIs('endorsements.trashed*') ? 'active' : '' }}" href="{{ route('endorsements.index') }}">Data Endorse</a>
                        <a class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Kelola User</a>
                    </div>
                    <div class="space-y-1">
                        <p class="px-2 text-xs uppercase tracking-[0.12em] text-muted-foreground">Actions</p>
                        <a class="sidebar-link {{ request()->routeIs('endorsements.create') ? 'active' : '' }}" href="{{ route('endorsements.create') }}">Tambah Data Endorse</a>
                        <a class="sidebar-link {{ request()->routeIs('endorsements.trashed*') ? 'active' : '' }}" href="{{ route('endorsements.trashed') }}">Arsip Hapus</a>
                        <a class="sidebar-link {{ request()->routeIs('password.form') ? 'active' : '' }}" href="{{ route('password.form') }}">Ganti Password</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="sidebar-link w-100 text-start">Keluar Akun</button>
                        </form>
                    </div>
                </nav>
            </div>
        </div>

        <div class="app-shell container-fluid py-4">
            <div class="sidebar-wrapper hidden lg:block">
                @include('layouts.sidebar')
            </div>
            <main class="app-main">
                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm">
                        <div class="fw-semibold mb-1">Berhasil</div>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm">
                        <div class="fw-semibold mb-2">Ada beberapa hal yang perlu diperiksa:</div>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    @else
        <div class="container py-4 main-shell">
            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm">
                    <div class="fw-semibold mb-1">Berhasil</div>
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm">
                    <div class="fw-semibold mb-2">Ada beberapa hal yang perlu diperiksa:</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (() => {
        const panel = document.getElementById('mobileMenuPanel');
        const openBtn = document.getElementById('mobileMenuBtn');
        const closeBtn = document.getElementById('mobileMenuClose');
        const closeX = document.getElementById('mobileMenuX');
        const toggle = (show) => {
            if (!panel) return;
            panel.classList.toggle('hidden', !show);
            document.body.style.overflow = show ? 'hidden' : '';
        };
        openBtn?.addEventListener('click', () => toggle(true));
        closeBtn?.addEventListener('click', () => toggle(false));
        closeX?.addEventListener('click', () => toggle(false));
    })();
</script>
@stack('scripts')
</body>
</html>
