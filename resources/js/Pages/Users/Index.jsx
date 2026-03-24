import React from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/formatters';

export default function UsersIndex({ users, filters, stats }) {
    const createForm = useForm({
        username: '',
        password: '',
        role: 'trial',
        trial_ends_at: '',
    });

    const filterForm = useForm({
        q: filters.q ?? '',
        role: filters.role ?? '',
    });

    const submitCreate = (event) => {
        event.preventDefault();
        createForm.post('/users', {
            preserveScroll: true,
            onSuccess: () => createForm.reset('username', 'password', 'trial_ends_at'),
        });
    };

    const submitFilter = (event) => {
        event.preventDefault();
        filterForm.get('/users', {
            preserveScroll: true,
            replace: true,
            data: Object.fromEntries(
                Object.entries(filterForm.data).filter(([, value]) => value !== ''),
            ),
        });
    };

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">Settings / User & access</p>
                        <h1 className="text-2xl font-semibold text-foreground">Kelola User</h1>
                        <p className="text-sm text-muted-foreground">Kelola akun, durasi sesi, dan aksi cepat seperti force logout atau hapus.</p>
                    </div>
                    <div className="text-sm text-muted-foreground">
                        Trial 2 jam | Berlangganan 8 jam | Master 8 jam
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <StatCard label="Total User" value={stats.total_users} />
                    <StatCard label="Trial" value={stats.trial_count} />
                    <StatCard label="Berlangganan" value={stats.paid_count} />
                    <StatCard label="Online (15 menit)" value={stats.online_count} />
                </div>

                <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <div className="mb-4">
                        <p className="text-sm text-muted-foreground">Quick action</p>
                        <h2 className="text-base font-semibold text-foreground">Tambah User</h2>
                    </div>
                    <form onSubmit={submitCreate} className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Field label="Username" error={createForm.errors.username}>
                            <Input onChange={(event) => createForm.setData('username', event.target.value)} placeholder="mis. johndoe" value={createForm.data.username} />
                        </Field>
                        <Field label="Password" hint="Berikan ke user" error={createForm.errors.password}>
                            <Input onChange={(event) => createForm.setData('password', event.target.value)} placeholder="min 4 karakter" value={createForm.data.password} />
                        </Field>
                        <Field label="Role" error={createForm.errors.role}>
                            <Select onChange={(event) => createForm.setData('role', event.target.value)} value={createForm.data.role}>
                                <option value="trial">Trial</option>
                                <option value="paid">Berlangganan</option>
                            </Select>
                        </Field>
                        <Field label="Trial Berakhir" hint="Kosongkan jika berlangganan." error={createForm.errors.trial_ends_at}>
                            <Input
                                disabled={createForm.data.role !== 'trial'}
                                onChange={(event) => createForm.setData('trial_ends_at', event.target.value)}
                                type="date"
                                value={createForm.data.trial_ends_at}
                            />
                        </Field>
                        <div className="md:col-span-2 xl:col-span-4 flex justify-end">
                            <button
                                className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                                disabled={createForm.processing}
                                type="submit"
                            >
                                {createForm.processing ? 'Menyimpan...' : 'Buat User'}
                            </button>
                        </div>
                    </form>
                </section>

                <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <div className="mb-4">
                        <p className="text-sm text-muted-foreground">Filter & pencarian</p>
                        <h2 className="text-base font-semibold text-foreground">Cari user cepat</h2>
                    </div>
                    <form onSubmit={submitFilter} className="grid grid-cols-1 gap-4 md:grid-cols-[2fr_1fr_auto_auto]">
                        <Field label="Username">
                            <Input onChange={(event) => filterForm.setData('q', event.target.value)} placeholder="mis. dhedhe" value={filterForm.data.q} />
                        </Field>
                        <Field label="Role">
                            <Select onChange={(event) => filterForm.setData('role', event.target.value)} value={filterForm.data.role}>
                                <option value="">Semua</option>
                                <option value="trial">Trial</option>
                                <option value="paid">Berlangganan</option>
                                <option value="master">Master</option>
                            </Select>
                        </Field>
                        <button
                            className="inline-flex items-center justify-center self-end rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                            type="submit"
                        >
                            Terapkan
                        </button>
                        <button
                            className="inline-flex items-center justify-center self-end rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                            onClick={() => filterForm.get('/users', { preserveScroll: true, replace: true })}
                            type="button"
                        >
                            Reset
                        </button>
                    </form>
                </section>

                <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">Users</p>
                            <h2 className="text-base font-semibold text-foreground">Daftar User</h2>
                        </div>
                        <span className="text-xs text-muted-foreground">Edit inline | Logout paksa | Hapus</span>
                    </div>

                    <div className="space-y-3">
                        {users.length === 0 && (
                            <div className="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                                Tidak ada user yang cocok dengan filter.
                            </div>
                        )}
                        {users.map((user) => (
                            <UserRow key={user.id} user={user} />
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function UserRow({ user }) {
    const form = useForm({
        trial_ends_at: user.trial_ends_at ?? '',
        active: user.active ? '1' : '0',
        password: '',
        role: user.role,
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(`/users/${user.id}`, {
            preserveScroll: true,
        });
    };

    const forceLogout = () => {
        if (!window.confirm(`Paksa logout ${user.username}?`)) {
            return;
        }

        router.post(`/users/${user.id}/force-logout`, {}, {
            preserveScroll: true,
        });
    };

    const destroy = () => {
        if (!window.confirm(`Hapus user ${user.username} beserta semua datanya?`)) {
            return;
        }

        router.delete(`/users/${user.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <article className="rounded-2xl border border-border p-4">
            <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-semibold text-foreground">{user.username}</h3>
                        <Badge tone={user.active ? 'success' : 'muted'}>{user.active ? 'Aktif' : 'Nonaktif'}</Badge>
                        <Badge tone={user.is_online ? 'success' : 'muted'}>{user.is_online ? 'Online' : 'Offline'}</Badge>
                        <Badge tone="muted">{user.role_label}</Badge>
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Trial berakhir: {user.trial_ends_at ? formatDate(user.trial_ends_at) : '-'}
                    </p>
                </div>

                {user.role === 'master' ? (
                    <div className="rounded-xl bg-muted px-3 py-2 text-sm font-semibold text-muted-foreground">Master</div>
                ) : (
                    <form onSubmit={submit} className="grid w-full gap-3 xl:max-w-4xl xl:grid-cols-[140px_150px_150px_1fr_auto_auto_auto]">
                        <Input
                            disabled={form.data.role !== 'trial'}
                            onChange={(event) => form.setData('trial_ends_at', event.target.value)}
                            type="date"
                            value={form.data.trial_ends_at}
                        />
                        <Select onChange={(event) => form.setData('role', event.target.value)} value={form.data.role}>
                            <option value="trial">Trial</option>
                            <option value="paid">Berlangganan</option>
                            <option value="master">Master</option>
                        </Select>
                        <Select onChange={(event) => form.setData('active', event.target.value)} value={form.data.active}>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </Select>
                        <Input
                            onChange={(event) => form.setData('password', event.target.value)}
                            placeholder="Reset password (opsional)"
                            value={form.data.password}
                        />
                        <button
                            className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            disabled={form.processing}
                            type="submit"
                        >
                            Simpan
                        </button>
                        <button
                            className="inline-flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100"
                            onClick={forceLogout}
                            type="button"
                        >
                            Logout
                        </button>
                        <button
                            className="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                            onClick={destroy}
                            type="button"
                        >
                            Hapus
                        </button>
                        {(form.errors.trial_ends_at || form.errors.active || form.errors.password || form.errors.role) && (
                            <p className="xl:col-span-7 text-xs text-red-600">
                                {form.errors.trial_ends_at || form.errors.active || form.errors.password || form.errors.role}
                            </p>
                        )}
                    </form>
                )}
            </div>
        </article>
    );
}

function StatCard({ label, value }) {
    return (
        <div className="rounded-3xl border border-border bg-white p-4 shadow-sm">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-1 text-2xl font-semibold text-foreground">{value}</p>
        </div>
    );
}

function Field({ label, hint, error, children }) {
    return (
        <div>
            <label className="mb-2 block text-sm font-medium text-foreground">{label}</label>
            {children}
            {hint && <p className="mt-1 text-xs text-muted-foreground">{hint}</p>}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}

function Input(props) {
    return (
        <input
            {...props}
            className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
        />
    );
}

function Select(props) {
    return (
        <select
            {...props}
            className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
        />
    );
}

function Badge({ children, tone }) {
    const toneClasses = {
        success: 'bg-emerald-100 text-emerald-700',
        muted: 'bg-muted text-foreground',
    };

    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${toneClasses[tone] ?? toneClasses.muted}`}>
            {children}
        </span>
    );
}
