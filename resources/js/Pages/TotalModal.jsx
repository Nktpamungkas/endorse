import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import { CircleDollarSign, Package2, ReceiptText, TrendingUp } from 'lucide-react';
import Pagination from '@/components/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatDate } from '@/lib/formatters';

function buildQuery(data) {
    return Object.fromEntries(
        Object.entries(data).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );
}

export default function TotalModal({ endorsements, summary, filters, statusOptions, platformOptions }) {
    const form = useForm({
        q: filters.q ?? '',
        status: filters.status ?? '',
        platform: filters.platform ?? '',
        sort: filters.sort ?? 'highest_modal',
        per_page: String(filters.per_page ?? 10),
    });

    const submit = (event) => {
        event.preventDefault();
        form.get('/total-modal', {
            preserveScroll: true,
            replace: true,
            data: buildQuery(form.data),
        });
    };

    const changeSelect = (key, value) => {
        const next = { ...form.data, [key]: value };
        form.setData(key, value);
        form.get('/total-modal', {
            preserveScroll: true,
            replace: true,
            data: buildQuery(next),
        });
    };

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full border border-border bg-muted/50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                Financial overview
                            </div>
                            <h1 className="mt-3 text-2xl font-semibold text-foreground">Total Modal</h1>
                            <p className="text-sm text-muted-foreground">Daftar seluruh endorse beserta modal produk, biaya lain, dan total modalnya dalam satu tampilan.</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href="/endorsements" className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                                Data Endorse
                            </Link>
                            <Link href="/endorsements/create" className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                                + Tambah Endorse
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard icon={CircleDollarSign} label="Akumulasi Modal" value={formatCurrency(summary.total_modal)} />
                    <SummaryCard icon={Package2} label="Modal Produk" value={formatCurrency(summary.total_product_cost)} />
                    <SummaryCard icon={ReceiptText} label="Biaya Lain" value={formatCurrency(summary.total_other_cost)} />
                    <SummaryCard icon={TrendingUp} label="Rata-rata per Endorse" value={formatCurrency(summary.average_modal)} />
                </div>

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-[1.6fr_1fr]">
                    <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                        <form onSubmit={submit} className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
                            <div className="xl:col-span-2">
                                <label className="mb-2 block text-sm font-medium text-foreground">Cari brand/campaign</label>
                                <input
                                    className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                    onChange={(event) => form.setData('q', event.target.value)}
                                    placeholder="contoh: Wardah"
                                    value={form.data.q}
                                />
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Status</label>
                                <select
                                    className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                    onChange={(event) => changeSelect('status', event.target.value)}
                                    value={form.data.status}
                                >
                                    <option value="">Semua</option>
                                    {Object.entries(statusOptions).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Platform</label>
                                <select
                                    className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                    onChange={(event) => changeSelect('platform', event.target.value)}
                                    value={form.data.platform}
                                >
                                    <option value="">Semua</option>
                                    {Object.entries(platformOptions).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-2 block text-sm font-medium text-foreground">Urutkan</label>
                                <select
                                    className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                    onChange={(event) => changeSelect('sort', event.target.value)}
                                    value={form.data.sort}
                                >
                                    <option value="highest_modal">Modal tertinggi</option>
                                    <option value="lowest_modal">Modal terendah</option>
                                    <option value="latest">Terbaru diupdate</option>
                                    <option value="oldest">Terlama diupdate</option>
                                </select>
                            </div>
                            <div className="md:col-span-2 xl:col-span-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                <div className="flex gap-2">
                                    <button className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90" type="submit">
                                        Terapkan
                                    </button>
                                    <Link href="/total-modal" className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                                        Reset
                                    </Link>
                                </div>
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-foreground">Per halaman</label>
                                    <select
                                        className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                        onChange={(event) => changeSelect('per_page', event.target.value)}
                                        value={form.data.per_page}
                                    >
                                        {[10, 25, 50, 100].map((value) => (
                                            <option key={value} value={value}>{value}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                        </form>
                    </section>

                    <aside className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                        <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">Highlight</p>
                        <h2 className="mt-2 text-base font-semibold text-foreground">Endorse dengan modal tertinggi</h2>
                        {summary.highest_modal_item ? (
                            <div className="mt-4 rounded-2xl border border-border bg-muted/40 p-4">
                                <p className="font-semibold text-foreground">{summary.highest_modal_item.brand_name}</p>
                                {summary.highest_modal_item.campaign_name && (
                                    <p className="text-xs text-muted-foreground">{summary.highest_modal_item.campaign_name}</p>
                                )}
                                <p className="mt-4 text-xs uppercase tracking-[0.12em] text-muted-foreground">Total modal</p>
                                <p className="mt-1 text-2xl font-semibold text-foreground">
                                    {formatCurrency(summary.highest_modal_item.total_cost)}
                                </p>
                                <Link href={`/endorsements/${summary.highest_modal_item.id}`} className="mt-4 inline-flex items-center text-sm font-semibold text-primary hover:underline">
                                    Buka detail endorse
                                </Link>
                            </div>
                        ) : (
                            <p className="mt-4 text-sm text-muted-foreground">Belum ada data endorse untuk ditampilkan.</p>
                        )}

                        <div className="mt-4 rounded-2xl border border-border bg-white p-4">
                            <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">Jumlah endorse</p>
                            <p className="mt-1 text-2xl font-semibold text-foreground">{summary.total_items}</p>
                        </div>
                    </aside>
                </div>

                <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-foreground">Daftar Modal Endorse</p>
                            <p className="text-xs text-muted-foreground">
                                Menampilkan {endorsements.from ?? 0}-{endorsements.to ?? 0} dari {endorsements.total ?? 0} endorse
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
                                    <th className="py-3 pr-4 text-right">Modal Produk</th>
                                    <th className="py-3 pr-4 text-right">Biaya Lain</th>
                                    <th className="py-3 pr-4 text-right">Total Modal</th>
                                    <th className="py-3 pr-4 text-right">Pendapatan</th>
                                    <th className="py-3 pr-4 text-right">Laba</th>
                                    <th className="py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {endorsements.data.length === 0 && (
                                    <tr>
                                        <td colSpan={10} className="py-6 text-center text-muted-foreground">Belum ada data modal untuk filter ini.</td>
                                    </tr>
                                )}
                                {endorsements.data.map((item) => (
                                    <tr key={item.id} className="transition hover:bg-muted/40">
                                        <td className="py-3 pr-4">
                                            <div className="font-semibold text-foreground">{item.brand_name}</div>
                                            {item.campaign_name && <div className="text-xs text-muted-foreground">{item.campaign_name}</div>}
                                        </td>
                                        <td className="py-3 pr-4">{item.platform_label}</td>
                                        <td className="py-3 pr-4">
                                            <span className="inline-flex items-center rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-foreground">
                                                {item.status_label}
                                            </span>
                                        </td>
                                        <td className="py-3 pr-4">{item.posting_date ? formatDate(item.posting_date) : '-'}</td>
                                        <td className="py-3 pr-4 text-right">{formatCurrency(item.product_cost)}</td>
                                        <td className="py-3 pr-4 text-right">{formatCurrency(item.other_cost)}</td>
                                        <td className="py-3 pr-4 text-right font-semibold text-foreground">{formatCurrency(item.total_cost)}</td>
                                        <td className="py-3 pr-4 text-right">{formatCurrency(item.total_income)}</td>
                                        <td className={`py-3 pr-4 text-right ${item.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                            {formatCurrency(item.net_profit)}
                                        </td>
                                        <td className="py-3 text-right">
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
                            <div className="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                                Belum ada data modal untuk filter ini.
                            </div>
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
                                    <MobileMeta label="Platform" value={item.platform_label} />
                                    <MobileMeta label="Posting" value={item.posting_date ? formatDate(item.posting_date) : '-'} />
                                    <MobileMeta label="Modal Produk" value={formatCurrency(item.product_cost)} />
                                    <MobileMeta label="Biaya Lain" value={formatCurrency(item.other_cost)} />
                                </div>

                                <div className="mt-4 rounded-2xl bg-muted/40 px-3 py-3">
                                    <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">Total modal</p>
                                    <p className="mt-1 text-xl font-semibold text-foreground">{formatCurrency(item.total_cost)}</p>
                                    <p className={`mt-2 text-sm font-semibold ${item.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                        Laba: {formatCurrency(item.net_profit)}
                                    </p>
                                </div>

                                <div className="mt-4 grid grid-cols-2 gap-2">
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

function SummaryCard({ icon: Icon, label, value }) {
    return (
        <div className="rounded-3xl border border-border bg-white p-4 shadow-sm">
            <div className="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-muted text-foreground">
                <Icon className="h-4 w-4" />
            </div>
            <p className="mt-4 text-sm text-muted-foreground">{label}</p>
            <p className="mt-1 text-2xl font-semibold text-foreground">{value}</p>
        </div>
    );
}

function MobileMeta({ label, value }) {
    return (
        <div>
            <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">{label}</p>
            <p className="mt-1 font-medium text-foreground">{value}</p>
        </div>
    );
}
