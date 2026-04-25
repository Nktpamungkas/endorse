import React, { useEffect, useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Chart } from 'chart.js/auto';
import { ArrowRight, BarChart3, CalendarClock, CircleDollarSign, Layers3, ReceiptText, X } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDate } from '@/lib/formatters';

const STATUS_HELPERS = {
    deal_masuk: 'Deal baru masuk dan sudah mulai dicatat.',
    pembelian_produk: 'Produk sedang dibeli atau masih menunggu dikirim.',
    pembuatan_draft: 'Konten sedang disiapkan.',
    menunggu_draft_ok: 'Draft sudah dikirim dan menunggu persetujuan.',
    revisi: 'Ada revisi dari brand yang perlu dikerjakan.',
    menunggu_posting: 'Konten siap tayang atau dijadwalkan.',
    menunggu_insight: 'Konten sudah tayang dan tinggal kirim laporan.',
    menunggu_payment: 'Pekerjaan selesai, tinggal menunggu pembayaran.',
    selesai: 'Semua tahap sudah selesai.',
};

const TOUR_STEPS = [
    {
        title: 'Ringkasan dashboard',
        description: 'Halaman ini merangkum performa endorse Anda. Dari sini Anda bisa cek laba, modal, status kerja, dan job yang perlu ditindak cepat.',
        target: 'hero',
    },
    {
        title: 'Kartu angka utama',
        description: 'Bagian ini menampilkan total laba bersih, pendapatan, modal, dan jumlah endorse yang masih menunggu payment.',
        target: 'stats',
    },
    {
        title: 'Grafik pendapatan vs modal',
        description: 'Gunakan grafik ini untuk melihat pola biaya dan pemasukan per bulan dengan cepat, tanpa buka laporan detail.',
        target: 'chart',
    },
    {
        title: 'Status endorse',
        description: 'Klik salah satu status untuk menyaring pekerjaan aktif. Cara ini paling cepat untuk tahu job mana yang sedang macet atau menumpuk.',
        target: 'status',
    },
    {
        title: 'Detail job dan Total Modal',
        description: 'Panel terakhir menampilkan daftar job dari status terpilih. Kalau ingin fokus khusus ke biaya per endorse, buka menu Total Modal di sidebar.',
        target: 'details',
    },
];

export default function Dashboard(props) {
    const {
        statusCounts,
        totalIncome,
        totalCost,
        netProfit,
        receivedNetProfit,
        waitingPayment,
        waitingPaymentItems,
        selectedStatus,
        selectedStatusItems,
        statusOptions,
        platformOptions,
        paymentStatusOptions,
        monthlyStats,
    } = props;

    const chartRef = useRef(null);
    const heroRef = useRef(null);
    const statsRef = useRef(null);
    const chartSectionRef = useRef(null);
    const statusSectionRef = useRef(null);
    const detailSectionRef = useRef(null);
    const [tourOpen, setTourOpen] = useState(false);
    const [tourIndex, setTourIndex] = useState(0);
    const [statusDrafts, setStatusDrafts] = useState({});
    const [updatingId, setUpdatingId] = useState(null);

    useEffect(() => {
        if (!chartRef.current) {
            return;
        }

        const ctx = chartRef.current.getContext('2d');
        const labels = monthlyStats
            .map((month) => month.month_key)
            .map((month) => new Date(month).toLocaleString('id-ID', { month: 'short', year: 'numeric' }));
        const income = monthlyStats.map((month) => Number(month.income));
        const cost = monthlyStats.map((month) => Number(month.cost));
        const hasSingleActiveMonth = income.filter((value) => value > 0).length <= 1 && cost.filter((value) => value > 0).length <= 1;

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Pendapatan',
                        data: income,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.12)',
                        borderWidth: 2,
                        pointRadius: hasSingleActiveMonth ? 4 : 2,
                        pointHoverRadius: 5,
                        fill: true,
                        tension: 0.25,
                    },
                    {
                        label: 'Modal',
                        data: cost,
                        borderColor: 'rgb(148, 163, 184)',
                        backgroundColor: 'rgba(148, 163, 184, 0.08)',
                        borderWidth: 2,
                        pointRadius: hasSingleActiveMonth ? 4 : 2,
                        pointHoverRadius: 5,
                        fill: true,
                        tension: 0.25,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { font: { size: 10 }, color: '#0f172a' } },
                },
                scales: {
                    x: { ticks: { color: '#475569' }, grid: { display: false } },
                    y: {
                        ticks: {
                            color: '#475569',
                            callback: (value) => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`,
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                },
            },
        });

        return () => chart.destroy();
    }, [monthlyStats]);

    useEffect(() => {
        if (!tourOpen) {
            return;
        }

        const refs = {
            hero: heroRef,
            stats: statsRef,
            chart: chartSectionRef,
            status: statusSectionRef,
            details: detailSectionRef,
        };

        refs[TOUR_STEPS[tourIndex].target]?.current?.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
    }, [tourIndex, tourOpen]);

    useEffect(() => {
        setStatusDrafts(
            Object.fromEntries(selectedStatusItems.map((item) => [item.id, item.status])),
        );
    }, [selectedStatusItems]);

    const highlighted = (target) => (
        tourOpen && TOUR_STEPS[tourIndex].target === target
            ? 'ring-2 ring-primary/30 shadow-lg shadow-primary/10'
            : ''
    );

    const updateStatusDraft = (endorsementId, status) => {
        setStatusDrafts((current) => ({
            ...current,
            [endorsementId]: status,
        }));
    };

    const submitQuickStatus = (endorsementId) => {
        const nextStatus = statusDrafts[endorsementId];
        if (!nextStatus) {
            return;
        }

        setUpdatingId(endorsementId);
        router.post(`/endorsements/${endorsementId}/status`, {
            status: nextStatus,
        }, {
            preserveScroll: true,
            onFinish: () => setUpdatingId(null),
        });
    };

    return (
        <AppLayout>
            <div className="space-y-6">
                <div ref={heroRef} className={cn('rounded-3xl border border-border bg-white p-5 shadow-sm', highlighted('hero'))}>
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div className="space-y-2">
                            <div className="inline-flex items-center gap-2 rounded-full border border-border bg-muted/50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                Ringkasan cepat
                            </div>
                            <div>
                                <h1 className="text-2xl font-semibold text-foreground">Dashboard</h1>
                                <p className="text-sm text-muted-foreground">Pantau pekerjaan aktif, tagihan, modal, dan laba dalam satu tampilan.</p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <button
                                className="inline-flex items-center gap-2 rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                                onClick={() => {
                                    setTourIndex(0);
                                    setTourOpen(true);
                                }}
                                type="button"
                            >
                                <Layers3 className="h-4 w-4" />
                                Tour
                            </button>
                            <Link href="/total-modal" className="inline-flex items-center gap-2 rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                                <CircleDollarSign className="h-4 w-4" />
                                Total Modal
                            </Link>
                            <Link href="/endorsements/create" className="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                                + Tambah Endorse
                            </Link>
                        </div>
                    </div>
                </div>

                <div ref={statsRef} className={cn('grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-4', highlighted('stats'))}>
                    <StatCard
                        label="Laba Bersih"
                        helper="Pendapatan dikurangi modal."
                        value={formatCurrency(netProfit)}
                        subLabel={`Sudah diterima: ${formatCurrency(receivedNetProfit)}`}
                        accent={netProfit >= 0 ? 'text-emerald-600' : 'text-red-600'}
                    />
                    <StatCard label="Total Pendapatan" helper="Fee dan reimburse yang tercatat." value={formatCurrency(totalIncome)} />
                    <StatCard label="Total Modal" helper="Modal produk dan biaya lain." value={formatCurrency(totalCost)} />
                    <StatCard label="Menunggu Pembayaran" helper="Endorse yang sudah selesai tapi belum dibayar." value={`${waitingPayment} endorse`} />
                </div>

                <div ref={chartSectionRef} className={cn('rounded-xl border border-border bg-white p-4 shadow-sm', highlighted('chart'))}>
                    <div className="mb-3 flex items-center justify-between">
                        <div>
                            <p className="text-xs text-muted-foreground">Tren</p>
                            <h2 className="text-sm font-semibold text-foreground">Pendapatan vs Modal (bulanan)</h2>
                            <p className="text-xs text-muted-foreground">Garis biru: uang masuk. Garis abu-abu: biaya keluar.</p>
                        </div>
                        <div className="inline-flex items-center gap-2 rounded-full bg-muted/50 px-3 py-1 text-xs text-muted-foreground">
                            <BarChart3 className="h-3.5 w-3.5" />
                            Grafik ringkas
                        </div>
                    </div>
                    <div className="h-36">
                        <canvas ref={chartRef} height="90" />
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div ref={statusSectionRef} className={cn('rounded-xl border border-border bg-white p-4 shadow-sm lg:col-span-2', highlighted('status'))}>
                        <div className="mb-3 flex items-center justify-between">
                            <div>
                                <h2 className="text-sm font-semibold text-foreground">Tahap Endorse</h2>
                                <p className="text-xs text-muted-foreground">Klik tahap untuk melihat pekerjaan yang sedang berjalan.</p>
                            </div>
                        </div>
                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            {Object.entries(statusOptions).map(([key, label]) => (
                                <Link
                                    key={key}
                                    href={`/dashboard?status_view=${key}`}
                                    className={cn(
                                        'flex items-center justify-between rounded-lg border border-border px-3 py-2 transition hover:border-primary/60 hover:bg-muted/60',
                                        selectedStatus === key && 'bg-primary/5 ring-1 ring-primary/40',
                                    )}
                                >
                                    <span className="min-w-0">
                                        <span className="block text-sm font-medium">{label}</span>
                                        <span className="mt-0.5 block text-xs leading-5 text-muted-foreground">
                                            {STATUS_HELPERS[key] ?? 'Tahap pekerjaan endorse.'}
                                        </span>
                                    </span>
                                    <span className={cn(
                                        'ml-3 inline-flex shrink-0 items-center justify-center rounded-full px-3 py-1 text-xs font-semibold',
                                        selectedStatus === key ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground',
                                    )}
                                    >
                                        {statusCounts[key] ?? 0}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-white p-4 shadow-sm">
                        <div className="mb-3 flex items-center justify-between">
                            <div>
                                <h2 className="text-sm font-semibold text-foreground">Tagihan Belum Dibayar</h2>
                                <p className="text-xs text-muted-foreground">Urut dari jatuh tempo terdekat.</p>
                            </div>
                            <Link href="/endorsements?status=menunggu_payment" className="text-xs font-semibold text-primary hover:underline">Lihat</Link>
                        </div>
                        <div className="max-h-96 space-y-2 overflow-y-auto">
                            {waitingPaymentItems.length === 0 && (
                                <div className="rounded-xl border border-dashed border-border bg-muted/30 px-4 py-6 text-sm text-muted-foreground">
                                    <p className="font-medium text-foreground">Tidak ada tagihan yang menunggu.</p>
                                    <p className="mt-1">Endorse dengan status Menunggu Pembayaran akan muncul di sini.</p>
                                </div>
                            )}
                            {waitingPaymentItems.map((item) => (
                                <PaymentReminderCard
                                    item={item}
                                    key={item.id}
                                    paymentStatusOptions={paymentStatusOptions}
                                    statusOptions={statusOptions}
                                />
                            ))}
                        </div>
                    </div>
                </div>

                <div ref={detailSectionRef} className={cn('space-y-4 rounded-xl border border-border bg-white p-4 shadow-sm', highlighted('details'))}>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-xs uppercase tracking-[0.15em] text-muted-foreground">Detail Tahap</p>
                            <h3 className="text-lg font-semibold">{statusOptions[selectedStatus] ?? selectedStatus}</h3>
                            <p className="text-xs text-muted-foreground">{STATUS_HELPERS[selectedStatus] ?? 'Daftar endorse pada tahap terpilih.'}</p>
                        </div>
                        <Link href={`/endorsements?status=${selectedStatus}`} className="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline">
                            Lihat di Daftar Endorse
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    </div>

                    <div className="hidden lg:block">
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="text-left text-muted-foreground">
                                    <tr className="border-b border-border">
                                        <th className="py-2">Brand</th>
                                        <th className="py-2">Platform</th>
                                        <th className="py-2">Posting</th>
                                        <th className="py-2">Laporan</th>
                                        <th className="py-2">Pembayaran</th>
                                        <th className="py-2 text-right">Laba Bersih</th>
                                        <th className="py-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {selectedStatusItems.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="py-6">
                                                <EmptyStatusState selectedStatus={selectedStatus} statusOptions={statusOptions} />
                                            </td>
                                        </tr>
                                    )}
                                    {selectedStatusItems.map((item) => (
                                        <tr key={item.id} className="transition hover:bg-muted/40">
                                            <td className="py-2">
                                                <div className="font-semibold">{item.brand_name}</div>
                                                {item.campaign_name && <div className="text-xs text-muted-foreground">{item.campaign_name}</div>}
                                            </td>
                                            <td className="py-2">{platformOptions[item.platform] ?? item.platform}</td>
                                            <td className="py-2">{item.posting_date ? formatDate(item.posting_date) : '-'}</td>
                                            <td className="py-2">
                                                {item.insight_sent_at ? (
                                                    <span className="font-semibold text-emerald-600">Terkirim</span>
                                                ) : item.insight_due_at ? (
                                                    <span className={new Date(item.insight_due_at) < new Date() ? 'font-semibold text-red-600' : 'text-foreground'}>
                                                        {formatDate(item.insight_due_at)}
                                                    </span>
                                                ) : (
                                                    '-'
                                                )}
                                            </td>
                                            <td className="py-2">{paymentStatusOptions[item.payment_status] ?? item.payment_status}</td>
                                            <td className={cn('py-2 text-right', item.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600')}>
                                                {formatCurrency(item.net_profit)}
                                            </td>
                                            <td className="py-2 text-right">
                                                <QuickStatusControl
                                                    item={item}
                                                    onStatusChange={updateStatusDraft}
                                                    onSubmit={submitQuickStatus}
                                                    statusDraft={statusDrafts[item.id] ?? item.status}
                                                    statusOptions={statusOptions}
                                                    updatingId={updatingId}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-3 lg:hidden">
                        {selectedStatusItems.length === 0 && <EmptyStatusState selectedStatus={selectedStatus} statusOptions={statusOptions} />}
                        {selectedStatusItems.map((item) => (
                            <div key={item.id} className="rounded-xl border border-border bg-white p-3 shadow-sm">
                                <div className="flex justify-between gap-2">
                                    <div>
                                        <p className="font-semibold">{item.brand_name}</p>
                                        {item.campaign_name && <p className="text-xs text-muted-foreground">{item.campaign_name}</p>}
                                    </div>
                                    <span className="rounded-full bg-muted px-2 py-1 text-xs text-foreground">
                                        {paymentStatusOptions[item.payment_status] ?? item.payment_status}
                                    </span>
                                </div>
                                <div className="mt-2 grid grid-cols-2 gap-2 text-xs text-muted-foreground">
                                    <div>Platform<br /><span className="font-semibold text-foreground">{platformOptions[item.platform] ?? item.platform}</span></div>
                                    <div>Posting<br /><span className="font-semibold text-foreground">{item.posting_date ? formatDate(item.posting_date) : '-'}</span></div>
                                    <div>Laporan<br />
                                        {item.insight_sent_at ? (
                                            <span className="font-semibold text-emerald-600">Terkirim</span>
                                        ) : item.insight_due_at ? (
                                            <span className={new Date(item.insight_due_at) < new Date() ? 'font-semibold text-red-600' : 'text-foreground'}>
                                                {formatDate(item.insight_due_at)}
                                            </span>
                                        ) : (
                                            '-'
                                        )}
                                    </div>
                                    <div>Pembayaran<br /><span className="font-semibold text-foreground">{paymentStatusOptions[item.payment_status] ?? item.payment_status}</span></div>
                                </div>
                                <div className={cn('mt-2 text-sm font-semibold', item.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600')}>
                                    Laba: {formatCurrency(item.net_profit)}
                                </div>
                                <div className="mt-3 grid grid-cols-1 gap-2">
                                    <QuickStatusControl
                                        item={item}
                                        mobile
                                        onStatusChange={updateStatusDraft}
                                        onSubmit={submitQuickStatus}
                                        statusDraft={statusDrafts[item.id] ?? item.status}
                                        statusOptions={statusOptions}
                                        updatingId={updatingId}
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            <TourDialog
                currentStep={TOUR_STEPS[tourIndex]}
                isOpen={tourOpen}
                onClose={() => setTourOpen(false)}
                onNext={() => {
                    if (tourIndex === TOUR_STEPS.length - 1) {
                        setTourOpen(false);
                        return;
                    }

                    setTourIndex((value) => value + 1);
                }}
                onPrev={() => setTourIndex((value) => Math.max(0, value - 1))}
                stepIndex={tourIndex}
                steps={TOUR_STEPS}
            />
        </AppLayout>
    );
}

function PaymentReminderCard({ item, paymentStatusOptions, statusOptions }) {
    const dueMeta = getDueMeta(item.payment_due_date);

    return (
        <div className="rounded-lg border border-border/60 bg-white p-3">
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="truncate font-semibold text-foreground">{item.brand_name}</p>
                    {item.campaign_name && <p className="truncate text-xs text-muted-foreground">{item.campaign_name}</p>}
                </div>
                <span className={cn('inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold', dueMeta.className)}>
                    <CalendarClock className="h-3.5 w-3.5" />
                    {dueMeta.label}
                </span>
            </div>
            <div className="mt-3 rounded-lg bg-muted/40 px-3 py-2">
                <div className="flex items-center justify-between gap-3">
                    <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                        <ReceiptText className="h-3.5 w-3.5" />
                        Perlu ditagih
                    </span>
                    <span className="text-sm font-semibold text-foreground">{formatCurrency(item.total_income)}</span>
                </div>
                <div className="mt-1 flex items-center justify-between gap-3 text-xs text-muted-foreground">
                    <span>Modal tercatat</span>
                    <span>{formatCurrency(item.total_cost)}</span>
                </div>
            </div>
            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                <div className="inline-flex flex-wrap items-center gap-2 text-xs">
                    <span className="rounded-full bg-amber-100 px-2 py-1 text-amber-700">
                        {statusOptions[item.status] ?? item.status}
                    </span>
                    <span className="rounded-full bg-slate-100 px-2 py-1 text-slate-700">
                        {paymentStatusOptions[item.payment_status] ?? item.payment_status}
                    </span>
                </div>
                <Link href={`/endorsements/${item.id}`} className="text-xs font-semibold text-primary hover:underline">
                    Detail
                </Link>
            </div>
        </div>
    );
}

function EmptyStatusState({ selectedStatus, statusOptions }) {
    return (
        <div className="rounded-xl border border-dashed border-border bg-muted/30 px-4 py-6 text-center">
            <p className="text-sm font-medium text-foreground">Belum ada endorse di status {statusOptions[selectedStatus] ?? selectedStatus}.</p>
            <p className="mt-1 text-xs text-muted-foreground">Kalau ada job baru, gunakan tombol Tambah Endorse dan pilih status yang sesuai.</p>
        </div>
    );
}

function getDueMeta(value) {
    if (!value) {
        return {
            label: 'Belum ada due date',
            className: 'bg-slate-100 text-slate-700',
        };
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(value);
    dueDate.setHours(0, 0, 0, 0);

    if (dueDate < today) {
        return {
            label: `Lewat ${formatDate(value)}`,
            className: 'bg-red-100 text-red-700',
        };
    }

    if (dueDate.getTime() === today.getTime()) {
        return {
            label: 'Jatuh tempo hari ini',
            className: 'bg-amber-100 text-amber-700',
        };
    }

    return {
        label: formatDate(value),
        className: 'bg-emerald-100 text-emerald-700',
    };
}

function QuickStatusControl({
    item,
    statusOptions,
    statusDraft,
    updatingId,
    onStatusChange,
    onSubmit,
    mobile = false,
}) {
    const isUpdating = updatingId === item.id;

    if (mobile) {
        return (
            <>
                <select
                    className="w-full rounded-md border border-border bg-white px-3 py-2 text-xs font-medium text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                    onChange={(event) => onStatusChange(item.id, event.target.value)}
                    value={statusDraft}
                >
                    {Object.entries(statusOptions).map(([key, label]) => (
                        <option key={key} value={key}>{label}</option>
                    ))}
                </select>
                <button
                    className="inline-flex items-center justify-center rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                    disabled={isUpdating}
                    onClick={() => onSubmit(item.id)}
                    type="button"
                >
                    {isUpdating ? 'Menyimpan...' : 'Simpan Status'}
                </button>
                <Link href={`/endorsements/${item.id}`} className="inline-flex items-center justify-center rounded-md border border-border px-3 py-2 text-center text-xs font-semibold text-foreground hover:bg-muted">
                    Detail
                </Link>
            </>
        );
    }

    return (
        <div className="inline-flex items-center gap-2">
            <select
                className="w-40 rounded-md border border-border bg-white px-3 py-1.5 text-xs font-medium text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                onChange={(event) => onStatusChange(item.id, event.target.value)}
                value={statusDraft}
            >
                {Object.entries(statusOptions).map(([key, label]) => (
                    <option key={key} value={key}>{label}</option>
                ))}
            </select>
            <button
                className="inline-flex items-center justify-center rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                disabled={isUpdating}
                onClick={() => onSubmit(item.id)}
                type="button"
            >
                {isUpdating ? 'Menyimpan...' : 'Update'}
            </button>
            <Link href={`/endorsements/${item.id}`} className="inline-flex items-center justify-center rounded-md border border-border px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-muted">
                Detail
            </Link>
        </div>
    );
}

function StatCard({ label, value, subLabel, helper, accent }) {
    return (
        <div className="rounded-xl border border-border bg-white p-4 shadow-sm">
            <p className="text-xs text-muted-foreground">{label}</p>
            {helper && <p className="mt-0.5 text-xs leading-5 text-muted-foreground">{helper}</p>}
            <p className={`text-2xl font-semibold ${accent ?? ''}`}>{value}</p>
            {subLabel && <p className="mt-1 text-xs text-muted-foreground">{subLabel}</p>}
        </div>
    );
}

function TourDialog({ isOpen, onClose, onPrev, onNext, stepIndex, steps, currentStep }) {
    if (!isOpen) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/50 px-4">
            <div className="w-full max-w-xl rounded-3xl border border-border bg-white p-5 shadow-2xl">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                            Dashboard Tour
                        </p>
                        <h3 className="mt-2 text-xl font-semibold text-foreground">{currentStep.title}</h3>
                    </div>
                    <button
                        className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground transition hover:bg-muted"
                        onClick={onClose}
                        type="button"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <p className="mt-4 text-sm leading-6 text-muted-foreground">{currentStep.description}</p>

                <div className="mt-5 flex items-center gap-2">
                    {steps.map((step, index) => (
                        <div
                            key={step.title}
                            className={cn(
                                'h-2 flex-1 rounded-full bg-muted',
                                index <= stepIndex && 'bg-primary',
                            )}
                        />
                    ))}
                </div>

                <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <span className="text-sm text-muted-foreground">
                        Step {stepIndex + 1} dari {steps.length}
                    </span>
                    <div className="flex gap-2">
                        <button
                            className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50"
                            disabled={stepIndex === 0}
                            onClick={onPrev}
                            type="button"
                        >
                            Sebelumnya
                        </button>
                        <button
                            className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                            onClick={onNext}
                            type="button"
                        >
                            {stepIndex === steps.length - 1 ? 'Selesai' : 'Berikutnya'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
