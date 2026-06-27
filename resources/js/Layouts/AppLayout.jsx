import React, { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Archive,
    ArrowDownCircle,
    ArrowUpCircle,
    BookOpen,
    Database,
    KeyRound,
    LayoutDashboard,
    ListChecks,
    LogOut,
    Menu,
    UserCog,
    Wallet,
    WalletCards,
    X,
} from 'lucide-react';
import { cn } from '@/lib/utils';

const NavLink = ({ href, active, children, icon: Icon, onClick }) => (
    <Link
        href={href}
        onClick={onClick}
        className={cn(
            'sidebar-link flex items-center gap-2',
            active ? 'active' : '',
        )}
    >
        {Icon && <Icon className="h-4 w-4 shrink-0 text-muted-foreground" />}
        {children}
    </Link>
);

export default function AppLayout({ children }) {
    const [open, setOpen] = useState(false);
    const { url, props } = usePage();
    const current = (url || '').split('?')[0];
    const currentUser = props.auth?.user ?? null;
    const isMaster = currentUser?.role === 'master';
    const isEndorseDataActive = current.startsWith('/endorsements')
        && !current.startsWith('/endorsements-deleted');
    const flash = props.flash ?? {};
    const errors = props.errors ?? {};
    const errorMessages = Object.values(errors).flat().filter(Boolean);
    useEffect(() => {
        setOpen(false);
    }, [url]);

    return (
        <div className="app-bg">
            <div className="mobile-safe-top sticky top-0 z-40 border-b border-border bg-white/90 backdrop-blur lg:hidden">
                <div className="mobile-safe-x flex items-center justify-between gap-3 px-4 py-3">
                    <div className="flex items-center gap-2">
                        <button
                            aria-label="Buka menu"
                            className="inline-flex items-center justify-center rounded-lg border border-border px-2 py-1 text-sm text-foreground"
                            onClick={() => setOpen((value) => !value)}
                            type="button"
                        >
                            <Menu className="h-4 w-4" />
                        </button>
                        <div>
                            <div className="text-sm font-semibold text-foreground">Endorse Tracker</div>
                            <div className="text-xs text-muted-foreground">Ringkasan cepat</div>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href="/dashboard" className="text-xs font-semibold text-foreground">
                            Dashboard
                        </Link>
                    </div>
                </div>
            </div>

            {open && (
                <div className="mobile-menu lg:hidden">
                    <div className="mobile-menu-overlay" onClick={() => setOpen(false)} />
                    <div className="mobile-menu-sheet mobile-safe-bottom">
                        <div className="flex items-center justify-between border-b border-border px-4 py-4">
                            <div>
                                <div className="text-sm font-semibold text-foreground">Endorse Tracker</div>
                                <div className="text-xs text-muted-foreground">Ringkasan cepat</div>
                            </div>
                            <button
                                aria-label="Tutup menu"
                                className="inline-flex items-center justify-center rounded-lg border border-border p-2 text-foreground"
                                onClick={() => setOpen(false)}
                                type="button"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        <nav className="space-y-4 p-3 text-sm">
                            <div className="space-y-1">
                                <p className="px-2 text-xs uppercase tracking-[0.12em] text-muted-foreground">Utama</p>
                                <NavLink href="/dashboard" active={current === '/dashboard'} icon={LayoutDashboard} onClick={() => setOpen(false)}>
                                    Dashboard
                                </NavLink>
                                <NavLink
                                    href="/endorsements"
                                    active={isEndorseDataActive}
                                    icon={ListChecks}
                                    onClick={() => setOpen(false)}
                                >
                                    Daftar Endorse
                                </NavLink>
                                <NavLink href="/total-modal" active={current.startsWith('/total-modal')} icon={WalletCards} onClick={() => setOpen(false)}>
                                    Total Modal
                                </NavLink>
                                <NavLink href="/pemasukan" active={current.startsWith('/pemasukan')} icon={ArrowUpCircle} onClick={() => setOpen(false)}>
                                    Pemasukan
                                </NavLink>
                                <NavLink href="/pengeluaran" active={current.startsWith('/pengeluaran')} icon={ArrowDownCircle} onClick={() => setOpen(false)}>
                                    Pengeluaran
                                </NavLink>
                                <NavLink href="/saldo" active={current.startsWith('/saldo')} icon={Wallet} onClick={() => setOpen(false)}>
                                    Saldo
                                </NavLink>
                                <NavLink href="/neraca" active={current.startsWith('/neraca')} icon={BookOpen} onClick={() => setOpen(false)}>
                                    Neraca
                                </NavLink>
                            </div>
                            <div className="space-y-1">
                                <p className="px-2 text-xs uppercase tracking-[0.12em] text-muted-foreground">Kelola</p>
                                <NavLink href="/users" active={current.startsWith('/users')} icon={UserCog} onClick={() => setOpen(false)}>
                                    Kelola Pengguna
                                </NavLink>
                                {isMaster && (
                                    <NavLink href="/database-backups" active={current.startsWith('/database-backups')} icon={Database} onClick={() => setOpen(false)}>
                                        Backup Database
                                    </NavLink>
                                )}
                                <NavLink href="/endorsements-deleted" active={current.startsWith('/endorsements-deleted')} icon={Archive} onClick={() => setOpen(false)}>
                                    Arsip Dihapus
                                </NavLink>
                            </div>
                            <div className="space-y-1">
                                <p className="px-2 text-xs uppercase tracking-[0.12em] text-muted-foreground">Akun</p>
                                <NavLink href="/profile/password" active={current.startsWith('/profile/password')} icon={KeyRound} onClick={() => setOpen(false)}>
                                    Ganti Password
                                </NavLink>
                                <button
                                    className="sidebar-link flex w-full items-center gap-2 text-left"
                                    onClick={() => { setOpen(false); router.post('/logout'); }}
                                    type="button"
                                >
                                    <LogOut className="h-4 w-4 shrink-0 text-muted-foreground" />
                                    Keluar
                                </button>
                            </div>
                        </nav>
                    </div>
                </div>
            )}

            <div className="app-shell container-fluid py-4">
                <div className="sidebar-wrapper hidden lg:block">
                    <aside className="sidebar-card rounded-3xl border border-border bg-white shadow-sm">
                        <div className="border-b border-border px-4 py-5">
                            <p className="mb-0 text-lg font-semibold text-foreground">Endorse Tracker</p>
                            <p className="mb-0 text-sm text-muted-foreground">Ringkasan cepat</p>
                        </div>
                        <nav className="space-y-4 p-3 text-sm">
                            <div className="space-y-1">
                                <p className="px-2 text-xs uppercase tracking-[0.12em] text-muted-foreground">Utama</p>
                                <NavLink href="/dashboard" active={current === '/dashboard'} icon={LayoutDashboard}>
                                    Dashboard
                                </NavLink>
                                <NavLink href="/endorsements" active={isEndorseDataActive} icon={ListChecks}>
                                    Daftar Endorse
                                </NavLink>
                                <NavLink href="/total-modal" active={current.startsWith('/total-modal')} icon={WalletCards}>
                                    Total Modal
                                </NavLink>
                                <NavLink href="/pemasukan" active={current.startsWith('/pemasukan')} icon={ArrowUpCircle}>
                                    Pemasukan
                                </NavLink>
                                <NavLink href="/pengeluaran" active={current.startsWith('/pengeluaran')} icon={ArrowDownCircle}>
                                    Pengeluaran
                                </NavLink>
                                <NavLink href="/saldo" active={current.startsWith('/saldo')} icon={Wallet}>
                                    Saldo
                                </NavLink>
                                <NavLink href="/neraca" active={current.startsWith('/neraca')} icon={BookOpen}>
                                    Neraca
                                </NavLink>
                            </div>
                            <div className="space-y-1">
                                <p className="px-2 text-xs uppercase tracking-[0.12em] text-muted-foreground">Kelola</p>
                                <NavLink href="/users" active={current.startsWith('/users')} icon={UserCog}>
                                    Kelola Pengguna
                                </NavLink>
                                {isMaster && (
                                    <NavLink href="/database-backups" active={current.startsWith('/database-backups')} icon={Database}>
                                        Backup Database
                                    </NavLink>
                                )}
                                <NavLink href="/endorsements-deleted" active={current.startsWith('/endorsements-deleted')} icon={Archive}>
                                    Arsip Dihapus
                                </NavLink>
                            </div>
                            <div className="space-y-1">
                                <p className="px-2 text-xs uppercase tracking-[0.12em] text-muted-foreground">Akun</p>
                                <NavLink href="/profile/password" active={current.startsWith('/profile/password')} icon={KeyRound}>
                                    Ganti Password
                                </NavLink>
                                <button
                                    className="sidebar-link flex w-full items-center gap-2 text-left"
                                    onClick={() => router.post('/logout')}
                                    type="button"
                                >
                                    <LogOut className="h-4 w-4 shrink-0 text-muted-foreground" />
                                    Keluar
                                </button>
                            </div>
                        </nav>
                    </aside>
                </div>

                <main className="app-main">
                    {flash.success && (
                        <div className="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {flash.success}
                        </div>
                    )}
                    {flash.error && (
                        <div className="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {flash.error}
                        </div>
                    )}
                    {errorMessages.length > 0 && (
                        <div className="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <div className="font-semibold">Ada input yang perlu diperbaiki:</div>
                            <ul className="mt-2 list-disc pl-5">
                                {errorMessages.map((message, index) => (
                                    <li key={`${message}-${index}`}>{message}</li>
                                ))}
                            </ul>
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}
