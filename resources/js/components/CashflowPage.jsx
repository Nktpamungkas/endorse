import React, { useEffect } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import Pagination from '@/components/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatCurrencyInput, formatDate, toCurrencyDigits } from '@/lib/formatters';

function buildQuery(data) {
    return Object.fromEntries(
        Object.entries(data).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );
}

function getTodayISODate() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export default function CashflowPage({
    title,
    description,
    routePrefix,
    items,
    summary,
    filters,
    editing,
    accentLabel,
}) {
    const createDefaultDate = getTodayISODate();
    const form = useForm({
        tanggal: editing?.tanggal ?? createDefaultDate,
        deskripsi: editing?.deskripsi ?? '',
        jumlah: editing ? toCurrencyDigits(editing.jumlah) : '',
    });

    const filterForm = useForm({
        q: filters.q ?? '',
        per_page: String(filters.per_page ?? 10),
    });

    useEffect(() => {
        form.setData(() => ({
            tanggal: editing?.tanggal ?? createDefaultDate,
            deskripsi: editing?.deskripsi ?? '',
            jumlah: editing ? toCurrencyDigits(editing.jumlah) : '',
        }));
    }, [editing, createDefaultDate]);

    const submitFilters = (event) => {
        event.preventDefault();
        filterForm.get(routePrefix, {
            preserveScroll: true,
            replace: true,
            data: buildQuery(filterForm.data),
        });
    };

    const setPerPage = (value) => {
        filterForm.setData('per_page', value);
        filterForm.get(routePrefix, {
            preserveScroll: true,
            replace: true,
            data: buildQuery({ ...filterForm.data, per_page: value }),
        });
    };

    const submitForm = (event) => {
        event.preventDefault();

        if (editing) {
            form.transform((data) => ({
                ...data,
                jumlah: data.jumlah || 0,
                _method: 'put',
            }));
            form.post(`${routePrefix}/${editing.id}`, {
                preserveScroll: true,
            });

            return;
        }

        form.transform((data) => ({
            ...data,
            jumlah: data.jumlah || 0,
        }));
        form.post(routePrefix, {
            preserveScroll: true,
        });
    };

    const removeItem = (item) => {
        if (!window.confirm(`Hapus data "${item.deskripsi}"?`)) {
            return;
        }

        router.post(`${routePrefix}/${item.id}`, {
            _method: 'delete',
        }, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full border border-border bg-muted/50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                {accentLabel}
                            </div>
                            <h1 className="mt-3 text-2xl font-semibold text-foreground">{title}</h1>
                            <p className="text-sm text-muted-foreground">{description}</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href="/saldo" className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                                Lihat Saldo
                            </Link>
                            <Link href="/dashboard" className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                                Kembali ke Dashboard
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <SummaryCard label="Total Data" value={`${summary.total_items} transaksi`} />
                    <SummaryCard label="Total Nominal" value={formatCurrency(summary.total_amount)} />
                </div>

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-[1.1fr_1.6fr]">
                    <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                        <div className="mb-4">
                            <p className="text-sm font-semibold text-foreground">{editing ? `Edit ${title}` : `Tambah ${title}`}</p>
                            <p className="text-xs text-muted-foreground">Simpan transaksi baru atau perbarui data yang sudah ada.</p>
                        </div>

                        <form onSubmit={submitForm} className="space-y-3">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Tanggal</label>
                                <input
                                    className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                    onChange={(event) => form.setData('tanggal', event.target.value)}
                                    type="date"
                                    value={form.data.tanggal}
                                />
                                {form.errors.tanggal && <p className="mt-1 text-xs text-red-600">{form.errors.tanggal}</p>}
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Deskripsi</label>
                                <input
                                    className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                    onChange={(event) => form.setData('deskripsi', event.target.value)}
                                    placeholder="Contoh: Fee live affiliate"
                                    value={form.data.deskripsi}
                                />
                                {form.errors.deskripsi && <p className="mt-1 text-xs text-red-600">{form.errors.deskripsi}</p>}
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Jumlah</label>
                                <div className="flex overflow-hidden rounded-xl border border-border bg-white focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/15">
                                    <span className="border-r border-border bg-muted px-3 py-2.5 text-sm text-muted-foreground">Rp</span>
                                    <input
                                        className="w-full rounded-none border-0 bg-white px-3 py-2.5 text-sm outline-none"
                                        inputMode="numeric"
                                        onChange={(event) => form.setData('jumlah', toCurrencyDigits(event.target.value))}
                                        placeholder="0"
                                        value={formatCurrencyInput(form.data.jumlah)}
                                    />
                                </div>
                                {form.errors.jumlah && <p className="mt-1 text-xs text-red-600">{form.errors.jumlah}</p>}
                            </div>

                            <div className="flex flex-col gap-2 pt-2 sm:flex-row">
                                <button
                                    className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                                    disabled={form.processing}
                                    type="submit"
                                >
                                    {form.processing ? 'Menyimpan...' : editing ? 'Update Data' : 'Tambah Data'}
                                </button>
                                <Link href={routePrefix} className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                                    {editing ? 'Batal Edit' : 'Reset Form'}
                                </Link>
                            </div>
                        </form>
                    </section>

                    <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                        <form onSubmit={submitFilters} className="mb-4 grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto_auto]">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Cari deskripsi</label>
                                <input
                                    className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                    onChange={(event) => filterForm.setData('q', event.target.value)}
                                    placeholder="Cari transaksi..."
                                    value={filterForm.data.q}
                                />
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Per halaman</label>
                                <select
                                    className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                    onChange={(event) => setPerPage(event.target.value)}
                                    value={filterForm.data.per_page}
                                >
                                    {[10, 25, 50, 100].map((value) => (
                                        <option key={value} value={value}>{value}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <button className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90" type="submit">
                                    Terapkan
                                </button>
                                <Link href={routePrefix} className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                                    Reset
                                </Link>
                            </div>
                        </form>

                        <div className="mb-4">
                            <p className="text-sm font-semibold text-foreground">Daftar {title}</p>
                            <p className="text-xs text-muted-foreground">
                                Menampilkan {items.from ?? 0}-{items.to ?? 0} dari {items.total ?? 0} data
                            </p>
                        </div>

                        <div className="hidden overflow-x-auto lg:block">
                            <table className="min-w-full text-sm">
                                <thead className="border-b border-border text-left text-muted-foreground">
                                    <tr>
                                        <th className="py-3 pr-4">Tanggal</th>
                                        <th className="py-3 pr-4">Deskripsi</th>
                                        <th className="py-3 pr-4 text-right">Jumlah</th>
                                        <th className="py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {items.data.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="py-6 text-center text-muted-foreground">Belum ada data untuk ditampilkan.</td>
                                        </tr>
                                    )}
                                    {items.data.map((item) => (
                                        <tr key={item.id} className="transition hover:bg-muted/40">
                                            <td className="py-3 pr-4 whitespace-nowrap">{formatDate(item.tanggal)}</td>
                                            <td className="py-3 pr-4">
                                                <div className="font-medium text-foreground">{item.deskripsi}</div>
                                            </td>
                                            <td className="py-3 pr-4 text-right font-semibold text-foreground whitespace-nowrap">
                                                {formatCurrency(item.jumlah)}
                                            </td>
                                            <td className="py-3 text-right whitespace-nowrap">
                                                <div className="inline-flex items-center gap-2">
                                                    <Link href={`${routePrefix}?edit=${item.id}`} className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-xs font-semibold text-foreground transition hover:bg-muted">
                                                        Edit
                                                    </Link>
                                                    <button
                                                        className="inline-flex items-center justify-center rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                                        onClick={() => removeItem(item)}
                                                        type="button"
                                                    >
                                                        Hapus
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="grid gap-3 lg:hidden">
                            {items.data.length === 0 && (
                                <div className="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                                    Belum ada data untuk ditampilkan.
                                </div>
                            )}
                            {items.data.map((item) => (
                                <article key={item.id} className="rounded-2xl border border-border bg-white p-4 shadow-sm">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-foreground">{item.deskripsi}</p>
                                            <p className="text-xs text-muted-foreground">{formatDate(item.tanggal)}</p>
                                        </div>
                                        <p className="font-semibold text-foreground">{formatCurrency(item.jumlah)}</p>
                                    </div>
                                    <div className="mt-4 grid grid-cols-2 gap-2">
                                        <Link href={`${routePrefix}?edit=${item.id}`} className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-sm font-semibold text-foreground transition hover:bg-muted">
                                            Edit
                                        </Link>
                                        <button
                                            className="inline-flex items-center justify-center rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                                            onClick={() => removeItem(item)}
                                            type="button"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </article>
                            ))}
                        </div>

                        <div className="mt-4">
                            <Pagination links={items.links} />
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function SummaryCard({ label, value }) {
    return (
        <div className="rounded-3xl border border-border bg-white p-4 shadow-sm">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-1 text-2xl font-semibold text-foreground">{value}</p>
        </div>
    );
}
