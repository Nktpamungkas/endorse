<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Endorse Tracker' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-start: #f4f8ff;
            --bg-end: #fff6ec;
            --card-bg: #ffffff;
            --ink: #132743;
            --muted: #5c6b7a;
            --primary: #0f4c81;
            --accent: #f39c12;
            --good: #218838;
            --warn: #f57f17;
            --danger: #c62828;
        }
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            min-height: 100vh;
            color: var(--ink);
            background: radial-gradient(circle at 10% 10%, #deecff 0%, transparent 35%),
                radial-gradient(circle at 80% 15%, #ffe8c7 0%, transparent 40%),
                linear-gradient(135deg, var(--bg-start), var(--bg-end));
        }
        .main-shell {
            max-width: 1700px;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .navbar-endorse {
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(15, 76, 129, 0.1);
        }
        .card-soft {
            background: var(--card-bg);
            border: 1px solid rgba(19, 39, 67, 0.08);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(15, 76, 129, 0.08);
        }
        .text-muted-soft {
            color: var(--muted);
        }
        .badge-status {
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-size: 0.78rem;
            font-weight: 700;
            background-color: #e8f1fc;
            color: var(--primary);
        }
        .table {
            width: 100%;
            max-width: 100%;
        }
        .table td,
        .table th {
            white-space: normal;
            word-break: break-word;
        }
        .table > :not(caption) > * > * {
            padding: 0.75rem 0.7rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        @media (min-width: 768px) {
            .table-responsive {
                overflow-x: visible;
            }
        }
        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .mobile-card-list {
            display: none;
        }
        .desktop-table {
            display: block;
        }
        .desktop-table.table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .endorse-table {
            min-width: 1300px;
        }
        .endorse-table td,
        .endorse-table th {
            vertical-align: middle;
        }
        .endorse-table .brand-cell {
            min-width: 220px;
            white-space: normal;
        }
        .endorse-table .cell-nowrap,
        .endorse-table .cell-actions,
        .endorse-table .profit-cell {
            white-space: nowrap;
        }
        .endorse-table .cell-modal {
            min-width: 200px;
        }
        .endorse-table .modal-breakdown {
            font-size: 0.78rem;
            color: var(--muted);
            white-space: nowrap;
        }
        @media (max-width: 1199.98px) {
            .main-shell {
                max-width: 100%;
            }
            .endorse-table {
                min-width: 1220px;
            }
        }
        .form-actions {
            display: flex;
            gap: 0.5rem;
        }
        @media (max-width: 767.98px) {
            body {
                font-size: 0.93rem;
            }
            .container.main-shell {
                padding-top: 0.9rem !important;
                padding-bottom: 1rem !important;
            }
            .navbar-endorse {
                border-radius: 12px !important;
                margin-bottom: 0.9rem !important;
                padding: 0.55rem 0.75rem !important;
            }
            .navbar-brand {
                font-size: 1rem;
            }
            .card-soft {
                border-radius: 12px;
                box-shadow: 0 8px 18px rgba(15, 76, 129, 0.08);
            }
            .card-soft.p-3 {
                padding: 0.85rem !important;
            }
            .h3 {
                font-size: 1.18rem;
            }
            .h4 {
                font-size: 1.05rem;
            }
            .h5 {
                font-size: 1rem;
            }
            .h6 {
                font-size: 0.92rem;
            }
            .form-label {
                margin-bottom: 0.22rem;
                font-size: 0.84rem;
            }
            .form-text {
                font-size: 0.76rem;
            }
            .table > :not(caption) > * > * {
                padding: 0.55rem 0.45rem;
            }
            .page-head {
                flex-direction: column;
                align-items: stretch;
                gap: 0.65rem;
                margin-bottom: 0.9rem !important;
            }
            .page-head .btn {
                width: 100%;
            }
            .desktop-table {
                display: none;
            }
            .mobile-card-list {
                display: grid;
                gap: 0.6rem;
            }
            .mobile-endorse-card {
                border: 1px solid rgba(19, 39, 67, 0.08);
                border-radius: 12px;
                background: #fff;
                padding: 0.7rem;
            }
            .mobile-endorse-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.4rem 0.65rem;
                margin-top: 0.4rem;
                font-size: 0.8rem;
            }
            .mobile-endorse-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.45rem;
                margin-top: 0.65rem;
            }
            .mobile-finance-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.4rem 0.65rem;
                margin-top: 0.55rem;
                font-size: 0.82rem;
            }
            .mobile-finance-row .label {
                color: var(--muted);
                font-size: 0.74rem;
            }
            .mobile-endorse-actions .btn {
                font-size: 0.78rem;
                padding: 0.35rem 0.45rem;
            }
            .badge-status {
                padding: 0.28rem 0.54rem;
                font-size: 0.68rem;
            }
            .form-actions {
                display: grid;
                grid-template-columns: 1fr;
            }
            .form-actions .btn {
                width: 100%;
            }
        }
        @media (max-width: 430px) {
            .mobile-endorse-grid,
            .mobile-finance-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container py-4 main-shell">
    @if(auth()->check())
        <nav class="navbar navbar-expand-lg rounded-4 px-3 mb-4 navbar-endorse">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">Endorse Tracker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topMenu">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('endorsements.*') ? 'active fw-semibold' : '' }}" href="{{ route('endorsements.index') }}">Data Endorse</a>
                    </li>
                    <li class="nav-item">
                        {{-- Selalu tampilkan bagi user yang sudah login; controller akan membatasi akses non-master --}}
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active fw-semibold' : '' }}" href="{{ route('users.index') }}">Kelola User</a>
                    </li>
                </ul>
                @if(auth()->check())
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#tourModal">Tour</button>
                        <span class="text-muted small">{{ auth()->user()->username }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-dark">Logout</button>
                        </form>
                    </div>
                @endif
            </div>
        </nav>
    @endif

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
