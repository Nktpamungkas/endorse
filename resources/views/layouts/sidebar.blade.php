<aside class="sidebar-card rounded-3xl border border-border bg-white shadow-sm">
    <div class="px-4 py-5 border-b border-border">
        <p class="text-lg fw-semibold mb-0 text-foreground">Endorse Tracker</p>
        <p class="text-muted-foreground mb-0">Lihat ringkasan pekerjaan</p>
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
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="sidebar-link w-100 text-start">Keluar Akun</button>
            </form>
        </div>
    </nav>
</aside>
