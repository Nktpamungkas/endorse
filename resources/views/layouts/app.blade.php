<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Endorse Tracker' }}</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%230f4c81'/%3E%3Cpath d='M18 12h28a4 4 0 0 1 4 4v32a4 4 0 0 1-4 4H18a4 4 0 0 1-4-4V16a4 4 0 0 1 4-4Z' fill='%23fff'/%3E%3Ccircle cx='32' cy='34' r='14' fill='%230f4c81'/%3E%3Ccircle cx='32' cy='34' r='10' fill='%23a6d8ff'/%3E%3Ccircle cx='24' cy='20' r='3' fill='%230f4c81'/%3E%3Ccircle cx='32' cy='20' r='3' fill='%230f4c81'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<div class="app-bg">
    @if(auth()->check())
        {{-- Mobile top bar --}}
        <div class="lg:hidden bg-white/90 backdrop-blur border-b border-border sticky top-0 z-40">
            <div class="px-4 py-3 flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-foreground">Endorse Tracker</div>
                    <div class="text-xs text-muted-foreground">Summary & monitoring</div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-foreground">Dashboard</a>
                    <a href="{{ route('endorsements.create') }}" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground">+ Tambah</a>
                </div>
            </div>
        </div>

        <div class="app-shell container-fluid py-4">
            <div class="sidebar-wrapper hidden lg:block">
                @include('layouts.sidebar')
            </div>
            <main class="app-main">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <div class="fw-semibold mb-2">Ada input yang perlu diperbaiki:</div>
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
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-2">Ada input yang perlu diperbaiki:</div>
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
@stack('scripts')
</body>
</html>
