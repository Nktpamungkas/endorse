import React, { useEffect, useRef } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Chart } from 'chart.js/auto';
import { formatCurrency } from '@/lib/formatters';

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

    useEffect(() => {
        if (!chartRef.current) return;
        const ctx = chartRef.current.getContext('2d');
        const labels = monthlyStats.map((m) => m.month_key).map((m) => new Date(m).toLocaleString('id-ID', { month: 'short', year: 'numeric' }));
        const income = monthlyStats.map((m) => Number(m.income));
        const cost = monthlyStats.map((m) => Number(m.cost));

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Pendapatan',
                        data: income,
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false,
                        tension: 0.25,
                    },
                    {
                        label: 'Modal',
                        data: cost,
                        borderColor: 'rgb(148, 163, 184)',
                        borderWidth: 2,
                        pointRadius: 0,
                        fill: false,
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
                            callback: (v) => 'Rp ' + new Intl.NumberFormat('id-ID').format(v),
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                },
            },
        });

        return () => chart.destroy();
    }, [monthlyStats]);

    return (
        <AppLayout auth={props.auth}>
            <div className="space-y-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold text-foreground">Dashboard</h1>
                        <p className="text-sm text-muted-foreground">Ringkasan endorse, insight, dan payment.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <button className="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted">
                            Tour
                        </button>
                        <Link href="/endorsements/create" className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition">
                            + Tambah Endorse
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-2">
                    <StatCard label="Laba Bersih" value={formatCurrency(netProfit)} subLabel={`Sudah diterima: ${formatCurrency(receivedNetProfit)}`} accent={netProfit >= 0 ? 'text-emerald-600' : 'text-red-600'} />
                    <StatCard label="Total Pendapatan" value={formatCurrency(totalIncome)} />
                    <StatCard label="Total Modal" value={formatCurrency(totalCost)} />
                    <StatCard label="Menunggu Payment" value={`${waitingPayment} endorse`} />
                </div>

                <div className="rounded-xl border border-border bg-white p-4 shadow-sm">
                    <div className="flex items-center justify-between mb-3">
                        <div>
                            <p className="text-xs text-muted-foreground">Tren</p>
                            <h2 className="text-sm font-semibold text-foreground">Pendapatan vs Modal (bulanan)</h2>
                        </div>
                    </div>
                    <div className="h-36">
                        <canvas ref={chartRef} height="90" />
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div className="lg:col-span-2 rounded-xl border border-border bg-white p-4 shadow-sm">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="text-sm font-semibold text-foreground">Status Endorse</h2>
                            <span className="text-xs text-muted-foreground">Klik untuk filter</span>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            {Object.entries(statusOptions).map(([key, label]) => (
                                <Link
                                    key={key}
                                    href={`/dashboard?status_view=${key}`}
                                    className={`flex items-center justify-between rounded-lg border border-border px-3 py-2 hover:border-primary/60 hover:bg-muted/60 transition ${selectedStatus === key ? 'bg-primary/5 ring-1 ring-primary/40' : ''}`}
                                >
                                    <span className="font-medium text-sm">{label}</span>
                                    <span className={`inline-flex items-center justify-center rounded-full px-3 py-1 text-xs font-semibold ${selectedStatus === key ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'}`}>
                                        {statusCounts[key] ?? 0}
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-white p-4 shadow-sm">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="text-sm font-semibold text-foreground">Payment Belum Lunas</h2>
                            <Link href="/endorsements?status=menunggu_payment" className="text-xs font-semibold text-primary hover:underline">Lihat</Link>
                        </div>
                        <div className="space-y-2 max-h-96 overflow-y-auto">
                            {waitingPaymentItems.length === 0 && (
                                <p className="text-sm text-muted-foreground">Semua payment sudah lunas.</p>
                            )}
                            {waitingPaymentItems.map((item) => (
                                <div key={item.id} className="rounded-lg border border-border/60 p-3 bg-white">
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <p className="font-semibold text-foreground">{item.brand_name}</p>
                                            {item.campaign_name && <p className="text-xs text-muted-foreground">{item.campaign_name}</p>}
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {item.payment_due_date ? new Date(item.payment_due_date).toLocaleDateString('id-ID') : 'Due ?'}
                                        </span>
                                    </div>
                                    <div className="mt-2 inline-flex items-center gap-2 text-xs">
                                        <span className="rounded-full bg-amber-100 px-2 py-1 text-amber-700">
                                            {statusOptions[item.status] ?? item.status}
                                        </span>
                                        <span className="rounded-full bg-slate-100 px-2 py-1 text-slate-700">
                                            {formatCurrency(item.total_cost)}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border border-border bg-white p-4 shadow-sm space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-xs text-muted-foreground uppercase tracking-[0.15em]">Detail Status</p>
                            <h3 className="text-lg font-semibold">{statusOptions[selectedStatus] ?? selectedStatus}</h3>
                        </div>
                        <Link href={`/endorsements?status=${selectedStatus}`} className="text-sm font-semibold text-primary hover:underline">Lihat di Data Endorse</Link>
                    </div>

                    <div className="hidden lg:block">
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="text-left text-muted-foreground">
                                <tr className="border-b border-border">
                                    <th className="py-2">Brand</th>
                                    <th className="py-2">Platform</th>
                                    <th className="py-2">Posting</th>
                                    <th className="py-2">Insight</th>
                                    <th className="py-2">Payment</th>
                                    <th className="py-2 text-right">Laba Bersih</th>
                                    <th className="py-2 text-right">Aksi</th>
                                </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                {selectedStatusItems.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="py-4 text-center text-muted-foreground">Tidak ada job di status ini.</td>
                                    </tr>
                                )}
                                {selectedStatusItems.map((item) => (
                                    <tr key={item.id} className="hover:bg-muted/40 transition">
                                        <td className="py-2">
                                            <div className="font-semibold">{item.brand_name}</div>
                                            {item.campaign_name && <div className="text-xs text-muted-foreground">{item.campaign_name}</div>}
                                        </td>
                                        <td className="py-2">{platformOptions[item.platform] ?? item.platform}</td>
                                        <td className="py-2">{item.posting_date ? new Date(item.posting_date).toLocaleDateString('id-ID') : '-'}</td>
                                        <td className="py-2">
                                            {item.insight_sent_at ? (
                                                <span className="text-emerald-600 font-semibold">Terkirim</span>
                                            ) : item.insight_due_at ? (
                                                <span className={new Date(item.insight_due_at) < new Date() ? 'text-red-600 font-semibold' : 'text-foreground'}>
                                                    {new Date(item.insight_due_at).toLocaleDateString('id-ID')}
                                                </span>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="py-2">{paymentStatusOptions[item.payment_status] ?? item.payment_status}</td>
                                        <td className={`py-2 text-right ${item.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                            {formatCurrency(item.net_profit)}
                                        </td>
                                        <td className="py-2 text-right">
                                            <div className="inline-flex items-center gap-2">
                                                <Link href={`/endorsements/${item.id}`} className="inline-flex items-center justify-center rounded-md border border-border px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-muted">Detail</Link>
                                                <Link href={`/endorsements/${item.id}/edit`} className="inline-flex items-center justify-center rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90">Edit</Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-3 lg:hidden">
                        {selectedStatusItems.length === 0 && <p className="text-sm text-muted-foreground">Tidak ada job di status ini.</p>}
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
                                <div className="grid grid-cols-2 gap-2 text-xs text-muted-foreground mt-2">
                                    <div>Platform<br /><span className="text-foreground font-semibold">{platformOptions[item.platform] ?? item.platform}</span></div>
                                    <div>Posting<br /><span className="text-foreground font-semibold">{item.posting_date ? new Date(item.posting_date).toLocaleDateString('id-ID') : '-'}</span></div>
                                    <div>Insight<br />
                                        {item.insight_sent_at ? (
                                            <span className="text-emerald-600 font-semibold">Terkirim</span>
                                        ) : item.insight_due_at ? (
                                            <span className={new Date(item.insight_due_at) < new Date() ? 'text-red-600 font-semibold' : 'text-foreground'}>
                                                {new Date(item.insight_due_at).toLocaleDateString('id-ID')}
                                            </span>
                                        ) : (
                                            '-'
                                        )}
                                    </div>
                                    <div>Payment<br /><span className="text-foreground font-semibold">{paymentStatusOptions[item.payment_status] ?? item.payment_status}</span></div>
                                </div>
                                <div className={`mt-2 text-sm font-semibold ${item.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                                    Laba: {formatCurrency(item.net_profit)}
                                </div>
                                <div className="mt-3 grid grid-cols-1 gap-2">
                                    <Link href={`/endorsements/${item.id}`} className="inline-flex items-center justify-center rounded-md border border-border px-3 py-2 text-xs font-semibold text-foreground hover:bg-muted text-center">Detail</Link>
                                    <Link href={`/endorsements/${item.id}/edit`} className="inline-flex items-center justify-center rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90 text-center">Edit</Link>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function StatCard({ label, value, subLabel, accent }) {
    return (
        <div className="rounded-xl border border-border bg-white p-4 shadow-sm">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className={`text-2xl font-semibold ${accent ?? ''}`}>{value}</p>
            {subLabel && <p className="text-xs text-muted-foreground mt-1">{subLabel}</p>}
        </div>
    );
}
