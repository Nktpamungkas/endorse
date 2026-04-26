import React from 'react';
import { Link } from '@inertiajs/react';
import { ArrowDownCircle, ArrowUpCircle, Landmark, Wallet } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatDate } from '@/lib/formatters';

export default function Saldo({ summary, recentPemasukan, recentPengeluaran }) {
    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full border border-border bg-muted/50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                Arus kas
                            </div>
                            <h1 className="mt-3 text-2xl font-semibold text-foreground">Saldo</h1>
                            <p className="text-sm text-muted-foreground">Saldo dihitung realtime dari endorse yang sudah dibayar, pemasukan tambahan, dan seluruh pengeluaran.</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href="/pemasukan" className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                                Kelola Pemasukan
                            </Link>
                            <Link href="/pengeluaran" className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                                Kelola Pengeluaran
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <SaldoCard icon={Landmark} label="Total Diterima" value={formatCurrency(summary.total_diterima)} helper="Dari endorse yang status pembayarannya sudah lunas." />
                    <SaldoCard icon={ArrowUpCircle} label="Total Pemasukan" value={formatCurrency(summary.total_pemasukan)} helper="Pemasukan tambahan di luar pembayaran endorse." />
                    <SaldoCard icon={ArrowDownCircle} label="Total Pengeluaran" value={formatCurrency(summary.total_pengeluaran)} helper="Akumulasi semua pengeluaran yang dicatat." />
                    <SaldoCard icon={Wallet} label="Saldo Akhir" value={formatCurrency(summary.saldo_akhir)} helper="Total diterima + pemasukan - pengeluaran." accent={summary.saldo_akhir >= 0 ? 'text-emerald-600' : 'text-red-600'} />
                </div>

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <RecentCard
                        title="Pemasukan Terbaru"
                        emptyText="Belum ada pemasukan tambahan."
                        href="/pemasukan"
                        items={recentPemasukan}
                    />
                    <RecentCard
                        title="Pengeluaran Terbaru"
                        emptyText="Belum ada pengeluaran tambahan."
                        href="/pengeluaran"
                        items={recentPengeluaran}
                    />
                </div>
            </div>
        </AppLayout>
    );
}

function SaldoCard({ icon: Icon, label, value, helper, accent = '' }) {
    return (
        <div className="rounded-3xl border border-border bg-white p-4 shadow-sm">
            <div className="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-muted text-foreground">
                <Icon className="h-4 w-4" />
            </div>
            <p className="mt-4 text-sm text-muted-foreground">{label}</p>
            <p className={`mt-1 text-2xl font-semibold text-foreground ${accent}`}>{value}</p>
            <p className="mt-1 text-xs text-muted-foreground">{helper}</p>
        </div>
    );
}

function RecentCard({ title, items, href, emptyText }) {
    return (
        <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
            <div className="mb-4 flex items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold text-foreground">{title}</p>
                    <p className="text-xs text-muted-foreground">5 data terbaru.</p>
                </div>
                <Link href={href} className="text-sm font-semibold text-primary hover:underline">
                    Buka
                </Link>
            </div>

            <div className="space-y-2">
                {items.length === 0 && (
                    <div className="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                        {emptyText}
                    </div>
                )}
                {items.map((item) => (
                    <div key={item.id} className="rounded-2xl border border-border bg-muted/30 px-4 py-3">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="font-medium text-foreground">{item.deskripsi}</p>
                                <p className="text-xs text-muted-foreground">{formatDate(item.tanggal)}</p>
                            </div>
                            <p className="font-semibold text-foreground">{formatCurrency(item.jumlah)}</p>
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
