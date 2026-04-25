import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/components/Pagination';
import { formatCurrency, formatDate } from '@/lib/formatters';

function buildQuery(data) {
    return Object.fromEntries(
        Object.entries(data).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );
}

export default function EndorsementsIndex({
    endorsements,
    statusOptions,
    paymentStatusOptions,
    filters,
}) {
    const filterForm = useForm({
        q: filters.q ?? '',
        status: filters.status ?? '',
        payment_status: filters.payment_status ?? '',
        insight: filters.insight ?? '',
        per_page: String(filters.per_page ?? 10),
    });

    const submitFilters = (event) => {
        event.preventDefault();
        filterForm.get('/endorsements', {
            preserveScroll: true,
            replace: true,
            data: buildQuery(filterForm.data),
        });
    };

    const setPerPage = (value) => {
        filterForm.setData('per_page', value);
        filterForm.get('/endorsements', {
            preserveScroll: true,
            replace: true,
            data: buildQuery({ ...filterForm.data, per_page: value }),
        });
    };

    const resetFilters = () => {
        filterForm.get('/endorsements', {
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">Daftar Endorse</h1>
                        <p className="text-sm text-muted-foreground">Tracking campaign, revisi, laporan, dan pembayaran.</p>
                    </div>
                    <Link
                        href="/endorsements/create"
                        className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                    >
                        + Tambah Endorse
                    </Link>
                </div>

                <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                    <form onSubmit={submitFilters} className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                        <div className="xl:col-span-2">
                            <label className="mb-2 block text-sm font-medium text-foreground">Cari brand / campaign</label>
                            <input
                                className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                name="q"
                                onChange={(event) => filterForm.setData('q', event.target.value)}
                                placeholder="contoh: Wardah"
                                value={filterForm.data.q}
                            />
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium text-foreground">Status kerja</label>
                            <select
                                className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                name="status"
                                onChange={(event) => filterForm.setData('status', event.target.value)}
                                value={filterForm.data.status}
                            >
                                <option value="">Semua status</option>
                                {Object.entries(statusOptions).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium text-foreground">Status pembayaran</label>
                            <select
                                className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                name="payment_status"
                                onChange={(event) => filterForm.setData('payment_status', event.target.value)}
                                value={filterForm.data.payment_status}
                            >
                                <option value="">Semua</option>
                                {Object.entries(paymentStatusOptions).map(([key, label]) => (
                                    <option key={key} value={key}>{label}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium text-foreground">Status laporan</label>
                            <select
                                className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                name="insight"
                                onChange={(event) => filterForm.setData('insight', event.target.value)}
                                value={filterForm.data.insight}
                            >
                                <option value="">Semua</option>
                                <option value="waiting">Menunggu Laporan</option>
                                <option value="overdue">Laporan Terlambat</option>
                                <option value="sent">Laporan Terkirim</option>
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium text-foreground">Per halaman</label>
                            <select
                                className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                onChange={(event) => setPerPage(event.target.value)}
                                value={filterForm.data.per_page}
                            >
                                {[10, 25, 50, 100].map((value) => (
                                    <option key={value} value={value}>{value}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-2 md:flex-row xl:col-span-6 xl:justify-between">
                            <div className="flex flex-1 flex-col gap-2 sm:flex-row">
                                <button
                                    className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                                    disabled={filterForm.processing}
                                    type="submit"
                                >
                                    {filterForm.processing ? 'Memfilter...' : 'Terapkan'}
                                </button>
                                <button
                                    className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                                    onClick={resetFilters}
                                    type="button"
                                >
                                    Reset
                                </button>
                            </div>
                            <a
                                href={`/endorsements-export?${new URLSearchParams(buildQuery(filterForm.data)).toString()}`}
                                className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                            >
                                Download Laporan CSV
                            </a>
                        </div>
                    </form>
                </section>

                <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-foreground">Daftar Endorse</p>
                            <p className="text-xs text-muted-foreground">
                                Menampilkan {endorsements.from ?? 0}-{endorsements.to ?? 0} dari {endorsements.total ?? 0} data
                            </p>
                        </div>
                    </div>

                    <div className="hidden overflow-x-auto lg:block">
                        <table className="min-w-full text-sm">
                            <thead className="border-b border-border text-left text-muted-foreground">
                                <tr>
                                    <th className="py-3 pr-4">Brand / Campaign</th>
                                    <th className="py-3 pr-4">Platform</th>
                                    <th className="py-3 pr-4">Status</th>
                                    <th className="py-3 pr-4">Posting</th>
                                    <th className="py-3 pr-4">Insight</th>
                                    <th className="py-3 pr-4">Payment</th>
                                    <th className="py-3 pr-4 text-right">Modal</th>
                                    <th className="py-3 pr-4 text-right">Laba</th>
                                    <th className="py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {endorsements.data.length === 0 && (
                                    <tr>
                                        <td colSpan={9} className="py-6">
                                            <EmptyEndorseState />
                                        </td>
                                    </tr>
                                )}
                                {endorsements.data.map((item) => (
                                    <tr key={item.id} className="transition hover:bg-muted/40">
                                        <td className="py-3 pr-4">
                                            <div className="font-semibold text-foreground">{item.brand_name}</div>
                                            {item.campaign_name && <div className="text-xs text-muted-foreground">{item.campaign_name}</div>}
                                        </td>
                                        <td className="py-3 pr-4 whitespace-nowrap">{item.platform_label}</td>
                                        <td className="py-3 pr-4 whitespace-nowrap">
                                            <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-foreground">
                                                {item.status_label}
                                            </span>
                                        </td>
                                        <td className="py-3 pr-4 whitespace-nowrap">{item.posting_date ? formatDate(item.posting_date) : '-'}</td>
                                        <td className="py-3 pr-4 whitespace-nowrap">
                                            {item.insight_sent_at ? (
                                                <span className="font-semibold text-emerald-600">Terkirim</span>
                                            ) : item.insight_due_at ? (
                                                <span className={item.is_insight_overdue ? 'font-semibold text-red-600' : 'text-foreground'}>
                                                    {formatDate(item.insight_due_at)}
                                                </span>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="py-3 pr-4 whitespace-nowrap">{item.payment_status_label}</td>
                                        <td className="py-3 pr-4 text-right whitespace-nowrap">
                                            <div className="font-semibold text-foreground">{formatCurrency(item.total_cost)}</div>
                                            <div className="text-xs text-muted-foreground">
                                                Produk {formatCurrency(item.product_cost)} | Lain {formatCurrency(item.other_cost)}
                                            </div>
                                        </td>
                                        <td className={`py-3 pr-4 text-right whitespace-nowrap ${item.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                            {formatCurrency(item.net_profit)}
                                        </td>
                                        <td className="py-3 text-right whitespace-nowrap">
                                            <div className="inline-flex items-center gap-2">
                                                <Link href={`/endorsements/${item.id}`} className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-xs font-semibold text-foreground transition hover:bg-muted">
                                                    Detail
                                                </Link>
                                                <Link href={`/endorsements/${item.id}/edit`} className="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90">
                                                    Edit
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="grid gap-3 lg:hidden">
                        {endorsements.data.length === 0 && (
                            <EmptyEndorseState />
                        )}
                        {endorsements.data.map((item) => (
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
                                <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
                                    <Meta label="Platform" value={item.platform_label} />
                                    <Meta label="Payment" value={item.payment_status_label} />
                                    <Meta label="Posting" value={item.posting_date ? formatDate(item.posting_date) : '-'} />
                                    <Meta
                                        label="Insight"
                                        value={item.insight_sent_at ? 'Terkirim' : item.insight_due_at ? formatDate(item.insight_due_at) : '-'}
                                        accent={item.is_insight_overdue ? 'text-red-600' : ''}
                                    />
                                </div>
                                <div className="mt-4 flex items-end justify-between gap-3 rounded-2xl bg-muted/40 px-3 py-3">
                                    <div className="text-sm">
                                        <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">Modal</p>
                                        <p className="font-semibold text-foreground">{formatCurrency(item.total_cost)}</p>
                                    </div>
                                    <div className="text-right text-sm">
                                        <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">Laba</p>
                                        <p className={item.net_profit >= 0 ? 'font-semibold text-emerald-600' : 'font-semibold text-red-600'}>
                                            {formatCurrency(item.net_profit)}
                                        </p>
                                    </div>
                                </div>
                                <div className="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <Link href={`/endorsements/${item.id}`} className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-sm font-semibold text-foreground transition hover:bg-muted">
                                        Detail
                                    </Link>
                                    <Link href={`/endorsements/${item.id}/edit`} className="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                                        Edit
                                    </Link>
                                </div>
                            </article>
                        ))}
                    </div>

                    <div className="mt-4">
                        <Pagination links={endorsements.links} />
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function EmptyEndorseState() {
    return (
        <div className="rounded-2xl border border-dashed border-border bg-muted/30 px-4 py-8 text-center">
            <p className="text-sm font-medium text-foreground">Belum ada data endorse yang sesuai.</p>
            <p className="mt-1 text-xs text-muted-foreground">Coba reset filter atau tambahkan endorse baru.</p>
            <div className="mt-4 flex flex-col justify-center gap-2 sm:flex-row">
                <Link href="/endorsements" className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-xs font-semibold text-foreground transition hover:bg-muted">
                    Reset Filter
                </Link>
                <Link href="/endorsements/create" className="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90">
                    Tambah Endorse
                </Link>
            </div>
        </div>
    );
}

function Meta({ label, value, accent = '' }) {
    return (
        <div>
            <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">{label}</p>
            <p className={`mt-1 font-medium text-foreground ${accent}`}>{value}</p>
        </div>
    );
}
