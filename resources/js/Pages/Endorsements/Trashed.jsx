import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/formatters';

export default function EndorsementsTrashed({ endorsements, filters }) {
    const form = useForm({
        q: filters.q ?? '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.get('/endorsements-deleted', {
            preserveScroll: true,
            replace: true,
            data: form.data.q ? { q: form.data.q } : {},
        });
    };

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">Arsip Dihapus</h1>
                        <p className="text-sm text-muted-foreground">Arsip data yang dibatalkan beserta alasan pembatalan.</p>
                    </div>
                    <Link
                        href="/endorsements"
                        className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                    >
                        Kembali ke Daftar Endorse
                    </Link>
                </div>

                <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                    <form onSubmit={submit} className="grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto_auto]">
                        <div>
                            <label className="mb-2 block text-sm font-medium text-foreground">Cari brand/campaign</label>
                            <input
                                className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                onChange={(event) => form.setData('q', event.target.value)}
                                placeholder="contoh: Wardah"
                                value={form.data.q}
                            />
                        </div>
                        <button
                            className="inline-flex items-center justify-center self-end rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                            type="submit"
                        >
                            Filter
                        </button>
                        <Link
                            href="/endorsements-deleted"
                            className="inline-flex items-center justify-center self-end rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                        >
                            Reset
                        </Link>
                    </form>
                </section>

                <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                    <div className="hidden overflow-x-auto lg:block">
                        <table className="min-w-full text-sm">
                            <thead className="border-b border-border text-left text-muted-foreground">
                                <tr>
                                    <th className="py-3 pr-4">Brand</th>
                                    <th className="py-3 pr-4">Status Terakhir</th>
                                    <th className="py-3 pr-4">Dihapus</th>
                                    <th className="py-3 pr-4">Alasan</th>
                                    <th className="py-3 pr-4">Dihapus oleh</th>
                                    <th className="py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {endorsements.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="py-6 text-center text-muted-foreground">Belum ada data yang dihapus.</td>
                                    </tr>
                                )}
                                {endorsements.map((item) => (
                                    <tr key={item.id} className="transition hover:bg-muted/40">
                                        <td className="py-3 pr-4">
                                            <div className="font-semibold text-foreground">{item.brand_name}</div>
                                            {item.campaign_name && <div className="text-xs text-muted-foreground">{item.campaign_name}</div>}
                                        </td>
                                        <td className="py-3 pr-4">
                                            <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-foreground">
                                                {item.status_label}
                                            </span>
                                        </td>
                                        <td className="py-3 pr-4 whitespace-nowrap">{item.deleted_at ? formatDate(item.deleted_at, { hour: '2-digit', minute: '2-digit' }) : '-'}</td>
                                        <td className="py-3 pr-4 text-sm text-foreground">{item.deleted_reason || '-'}</td>
                                        <td className="py-3 pr-4 whitespace-nowrap">{item.deleted_by_name || '-'}</td>
                                        <td className="py-3 text-right">
                                            <Link href={`/endorsements-deleted/${item.id}`} className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-xs font-semibold text-foreground transition hover:bg-muted">
                                                Detail
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="grid gap-3 lg:hidden">
                        {endorsements.length === 0 && (
                            <div className="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                                Belum ada data yang dihapus.
                            </div>
                        )}
                        {endorsements.map((item) => (
                            <article key={item.id} className="rounded-2xl border border-border bg-white p-4 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 className="font-semibold text-foreground">{item.brand_name}</h3>
                                        {item.campaign_name && <p className="text-xs text-muted-foreground">{item.campaign_name}</p>}
                                    </div>
                                    <span className="rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-foreground">
                                        {item.status_label}
                                    </span>
                                </div>
                                <div className="mt-4 grid grid-cols-1 gap-3 text-sm">
                                    <Meta label="Dihapus" value={item.deleted_at ? formatDate(item.deleted_at, { hour: '2-digit', minute: '2-digit' }) : '-'} />
                                    <Meta label="Alasan" value={item.deleted_reason || '-'} />
                                    <Meta label="Dihapus oleh" value={item.deleted_by_name || '-'} />
                                </div>
                                <Link href={`/endorsements-deleted/${item.id}`} className="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-border px-3 py-2 text-sm font-semibold text-foreground transition hover:bg-muted">
                                    Detail
                                </Link>
                            </article>
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function Meta({ label, value }) {
    return (
        <div>
            <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">{label}</p>
            <p className="mt-1 text-sm font-medium text-foreground">{value}</p>
        </div>
    );
}
