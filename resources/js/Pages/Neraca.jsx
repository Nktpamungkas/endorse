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
    return [{ value: 0, label: 'Semua Tahun' }, ...Array.from({ length: 5 }, (_, i) => ({ value: current - i, label: String(current - i) }))];
}

export default function Neraca({ rows, summary, filters, saldoPembuka }) {
    const now = new Date();
    const filterForm = useForm({
        bulan: String(filters.bulan ?? (now.getMonth() + 1)),
        tahun: String(filters.tahun ?? now.getFullYear()),
    });

    const applyFilter = (key, value) => {
        const next = { ...filterForm.data, [key]: value };
        // Kalau ganti ke "Semua Tahun", reset bulan juga
        if (key === 'tahun' && value === '0') {
            next.bulan = '0';
        }
        filterForm.setData(next);
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
                                className="rounded-xl border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none disabled:opacity-50"
                                value={filterForm.data.bulan}
                                disabled={filterForm.data.tahun === '0'}
                                onChange={(e) => applyFilter('bulan', e.target.value)}
                            >
                                {BULAN_OPTIONS.map((opt) => (
                                    <option key={opt.value} value={String(opt.value)}>{opt.label}</option>
                                ))}
                            </select>
                            <select
                                className="rounded-xl border border-border bg-white px-3 py-2 text-sm text-foreground focus:outline-none"
                                value={filterForm.data.tahun}
                                onChange={(e) => applyFilter('tahun', e.target.value)}
                            >
                                {buildYearOptions().map((opt) => (
                                    <option key={opt.value} value={String(opt.value)}>{opt.label}</option>
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
                            <table className="w-full border-collapse text-sm">
                                <thead>
                                    <tr>
                                        <th className="border border-border px-3 py-2 text-left">Tanggal</th>
                                        <th className="border border-border px-3 py-2 text-left">Keterangan</th>
                                        <th className="border border-border px-3 py-2 text-right">Debit</th>
                                        <th className="border border-border px-3 py-2 text-right">Kredit</th>
                                        <th className="border border-border px-3 py-2 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {saldoPembuka !== 0 && (
                                        <tr>
                                            <td className="border border-border px-3 py-2 italic text-muted-foreground" colSpan={2}>
                                                Saldo awal periode
                                            </td>
                                            <td className="border border-border px-3 py-2" />
                                            <td className="border border-border px-3 py-2" />
                                            <td className="border border-border px-3 py-2 text-right">
                                                {formatCurrency(saldoPembuka)}
                                            </td>
                                        </tr>
                                    )}
                                    {rows.map((row, idx) => (
                                        <tr key={`${row.tipe}-${row.ref_id}-${idx}`}>
                                            <td className="border border-border px-3 py-2 whitespace-nowrap">
                                                {formatDate(row.tanggal, { day: '2-digit', month: 'long', year: 'numeric' })}
                                            </td>
                                            <td className="border border-border px-3 py-2">
                                                [{TIPE_LABEL[row.tipe]}] {row.keterangan}
                                            </td>
                                            <td className="border border-border px-3 py-2 text-right whitespace-nowrap text-emerald-600">
                                                {row.debit > 0 ? formatCurrency(row.debit) : ''}
                                            </td>
                                            <td className="border border-border px-3 py-2 text-right whitespace-nowrap text-rose-600">
                                                {row.kredit > 0 ? formatCurrency(row.kredit) : ''}
                                            </td>
                                            <td className="border border-border px-3 py-2 text-right whitespace-nowrap">
                                                {formatCurrency(row.saldo)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td className="border border-border px-3 py-2 font-semibold" colSpan={2}>Total</td>
                                        <td className="border border-border px-3 py-2 text-right font-semibold whitespace-nowrap">{formatCurrency(summary.total_debit)}</td>
                                        <td className="border border-border px-3 py-2 text-right font-semibold whitespace-nowrap">{formatCurrency(summary.total_kredit)}</td>
                                        <td className="border border-border px-3 py-2 text-right font-semibold whitespace-nowrap">{formatCurrency(summary.saldo_akhir)}</td>
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
