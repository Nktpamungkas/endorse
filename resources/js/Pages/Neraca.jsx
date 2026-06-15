import React from 'react';
import { router, useForm } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatDate } from '@/lib/formatters';

const BULAN_OPTIONS = [
    { value: 0, label: 'Semua Bulan' },
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const TIPE_BADGE = {
    endorsement: 'bg-blue-50 text-blue-700 border-blue-200',
    pemasukan: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    pengeluaran: 'bg-rose-50 text-rose-700 border-rose-200',
};

const TIPE_LABEL = {
    endorsement: 'Endorse',
    pemasukan: 'Pemasukan',
    pengeluaran: 'Pengeluaran',
};

function buildYearOptions() {
    const current = new Date().getFullYear();
    return Array.from({ length: 5 }, (_, i) => current - i);
}

export default function Neraca({ rows, summary, filters }) {
    const filterForm = useForm({
        bulan: String(filters.bulan ?? 0),
        tahun: String(filters.tahun ?? new Date().getFullYear()),
    });

    const applyFilter = (key, value) => {
        const next = { ...filterForm.data, [key]: value };
        filterForm.setData(key, value);
        router.get('/neraca', next, { preserveScroll: true, preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <div className="space-y-4">
                {/* Header */}
                <div className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full border border-border bg-muted/50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                Keuangan
                            </div>
                            <h1 className="mt-3 text-2xl font-semibold text-foreground">Neraca</h1>
                            <p className="text-sm text-muted-foreground">
                                Riwayat transaksi debit dan kredit dari endorse, pemasukan, dan pengeluaran.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <select
                                className="rounded-xl border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none"
                                value={filterForm.data.bulan}
                                onChange={(e) => applyFilter('bulan', e.target.value)}
                            >
                                {BULAN_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                            <select
                                className="rounded-xl border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none"
                                value={filterForm.data.tahun}
                                onChange={(e) => applyFilter('tahun', e.target.value)}
                            >
                                {buildYearOptions().map((y) => (
                                    <option key={y} value={y}>{y}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                {/* Ringkasan */}
                <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <SummaryCard label="Total Debit (Masuk)" value={summary.total_debit} accent="text-emerald-600" />
                    <SummaryCard label="Total Kredit (Keluar)" value={summary.total_kredit} accent="text-rose-600" />
                    <SummaryCard
                        label="Saldo Akhir"
                        value={summary.saldo_akhir}
                        accent={summary.saldo_akhir >= 0 ? 'text-emerald-600' : 'text-rose-600'}
                    />
                </div>

                {/* Tabel */}
                <div className="rounded-3xl border border-border bg-white shadow-sm">
                    <div className="flex items-center gap-2 border-b border-border px-5 py-4">
                        <BookOpen className="h-4 w-4 text-muted-foreground" />
                        <p className="text-sm font-semibold text-foreground">Buku Kas</p>
                        <span className="ml-auto text-xs text-muted-foreground">{rows.length} transaksi</span>
                    </div>

                    {rows.length === 0 ? (
                        <div className="px-5 py-12 text-center text-sm text-muted-foreground">
                            Tidak ada transaksi pada periode ini.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border bg-muted/30 text-xs uppercase tracking-wide text-muted-foreground">
                                        <th className="px-4 py-3 text-left font-semibold">Tanggal</th>
                                        <th className="px-4 py-3 text-left font-semibold">Keterangan</th>
                                        <th className="px-4 py-3 text-right font-semibold">Debit</th>
                                        <th className="px-4 py-3 text-right font-semibold">Kredit</th>
                                        <th className="px-4 py-3 text-right font-semibold">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {rows.map((row, idx) => (
                                        <tr key={`${row.tipe}-${row.ref_id}-${idx}`} className="hover:bg-muted/20">
                                            <td className="whitespace-nowrap px-4 py-3 text-muted-foreground">
                                                {formatDate(row.tanggal)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span
                                                        className={`inline-block rounded-full border px-2 py-0.5 text-xs font-semibold ${TIPE_BADGE[row.tipe]}`}
                                                    >
                                                        {TIPE_LABEL[row.tipe]}
                                                    </span>
                                                    <span className="text-foreground">{row.keterangan}</span>
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right font-medium text-emerald-600">
                                                {row.debit > 0 ? formatCurrency(row.debit) : <span className="text-muted-foreground">—</span>}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-right font-medium text-rose-600">
                                                {row.kredit > 0 ? formatCurrency(row.kredit) : <span className="text-muted-foreground">—</span>}
                                            </td>
                                            <td className={`whitespace-nowrap px-4 py-3 text-right font-semibold ${row.saldo >= 0 ? 'text-foreground' : 'text-rose-600'}`}>
                                                {formatCurrency(row.saldo)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr className="border-t-2 border-border bg-muted/30 text-sm font-semibold">
                                        <td className="px-4 py-3 text-muted-foreground" colSpan={2}>Total</td>
                                        <td className="px-4 py-3 text-right text-emerald-600">{formatCurrency(summary.total_debit)}</td>
                                        <td className="px-4 py-3 text-right text-rose-600">{formatCurrency(summary.total_kredit)}</td>
                                        <td className={`px-4 py-3 text-right ${summary.saldo_akhir >= 0 ? 'text-foreground' : 'text-rose-600'}`}>
                                            {formatCurrency(summary.saldo_akhir)}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

function SummaryCard({ label, value, accent }) {
    return (
        <div className="rounded-3xl border border-border bg-white p-4 shadow-sm">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className={`mt-1 text-2xl font-semibold ${accent}`}>{formatCurrency(value)}</p>
        </div>
    );
}
