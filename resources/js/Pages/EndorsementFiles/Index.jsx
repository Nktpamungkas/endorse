import React from 'react';
import { useForm } from '@inertiajs/react';
import { HardDrive, Search } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import EndorsementFileBrowser from '@/components/EndorsementFileBrowser';
import EndorsementFileUploadCard from '@/components/EndorsementFileUploadCard';
import Pagination from '@/components/Pagination';
import { formatBytes, formatDate } from '@/lib/formatters';
import { cn } from '@/lib/utils';

function buildQuery(data) {
    return Object.fromEntries(
        Object.entries(data).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );
}

export default function EndorsementFilesIndex({
    files,
    filters,
    stats,
    endorsementOptions,
    categoryOptions,
    sortOptions,
    maxUploadMb,
    maxFilesPerRequest,
}) {
    const filterForm = useForm({
        q: filters.q ?? '',
        endorsement_id: filters.endorsement_id ?? '',
        category: filters.category ?? '',
        sort: filters.sort ?? 'latest',
        per_page: String(filters.per_page ?? 10),
    });

    const submitFilters = (event) => {
        event.preventDefault();
        filterForm.get('/endorsement-files', {
            preserveScroll: true,
            replace: true,
            data: buildQuery(filterForm.data),
        });
    };

    const resetFilters = () => {
        filterForm.get('/endorsement-files', {
            preserveScroll: true,
            replace: true,
        });
    };

    const setPerPage = (value) => {
        filterForm.setData('per_page', value);
        filterForm.get('/endorsement-files', {
            preserveScroll: true,
            replace: true,
            data: buildQuery({ ...filterForm.data, per_page: value }),
        });
    };

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">Penyimpanan File</h1>
                        <p className="text-sm text-muted-foreground">
                            Tempat simpan draft, foto, video, dan dokumen endorse. File tetap asli tanpa kompresi.
                        </p>
                    </div>
                    <div className="inline-flex items-center gap-2 rounded-2xl border border-border bg-white px-4 py-2 text-sm text-muted-foreground shadow-sm">
                        <HardDrive className="h-4 w-4" />
                        {stats.storage_available
                            ? `Sisa VPS ${formatBytes(stats.storage_free_bytes)}`
                            : 'Statistik storage VPS belum tersedia'}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard label="Total file" value={stats.total_files} note="Semua file yang sudah Anda upload" />
                    <SummaryCard label="Total storage" value={formatBytes(stats.total_size)} note="Akumulasi ukuran file tersimpan" />
                    <SummaryCard label="Endorse punya file" value={stats.endorsements_with_files} note="Jumlah endorse dengan arsip file" />
                    <SummaryCard
                        label="Upload terakhir"
                        value={stats.latest_upload_at ? formatDate(stats.latest_upload_at, { hour: '2-digit', minute: '2-digit' }) : '-'}
                        note="Aktivitas terbaru di penyimpanan"
                    />
                </div>

                <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <SummaryCard
                        label="Kapasitas disk"
                        value={stats.storage_available ? formatBytes(stats.storage_total_bytes) : '-'}
                        note={stats.storage_available ? 'Total kapasitas disk tempat file endorse disimpan' : 'Belum bisa membaca kapasitas disk server'}
                    />
                    <SummaryCard
                        label="Sisa storage VPS"
                        value={stats.storage_available ? formatBytes(stats.storage_free_bytes) : '-'}
                        note={stats.storage_available ? 'Ruang kosong aktual pada disk server' : 'Pastikan disk storage lokal bisa dibaca server'}
                    />
                    <SummaryCard
                        label="Pemakaian disk"
                        value={stats.storage_available ? `${stats.storage_used_percentage}%` : '-'}
                        note={stats.storage_available ? `Terpakai ${formatBytes(stats.storage_used_bytes)} dari kapasitas disk` : 'Statistik pemakaian disk belum tersedia'}
                    />
                </div>

                {stats.storage_available && stats.storage_root && (
                    <div className="rounded-2xl border border-border bg-white px-4 py-3 text-xs text-muted-foreground shadow-sm">
                        Disk yang dibaca: <span className="font-medium text-foreground">{stats.storage_root}</span>
                    </div>
                )}

                {stats.storage_available && (
                    <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                        <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p className="text-sm font-semibold text-foreground">Kapasitas disk server</p>
                                <p className="text-xs text-muted-foreground">
                                    Progress ini menunjukkan kondisi disk VPS tempat file endorse disimpan.
                                </p>
                            </div>
                            <div className={cn(
                                'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                                stats.alert_level === 'critical' && 'bg-red-100 text-red-700',
                                stats.alert_level === 'warning' && 'bg-amber-100 text-amber-700',
                                stats.alert_level === 'normal' && 'bg-emerald-100 text-emerald-700',
                            )}>
                                {stats.alert_level === 'critical' && 'Storage hampir penuh'}
                                {stats.alert_level === 'warning' && 'Storage mulai menipis'}
                                {stats.alert_level === 'normal' && 'Storage masih aman'}
                            </div>
                        </div>

                        <div className="mt-4">
                            <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                                <span className="font-medium text-foreground">
                                    Terpakai {formatBytes(stats.storage_used_bytes)} dari {formatBytes(stats.storage_total_bytes)}
                                </span>
                                <span className="text-muted-foreground">{stats.storage_used_percentage}%</span>
                            </div>
                            <div className="h-3 overflow-hidden rounded-full bg-muted">
                                <div
                                    className={cn(
                                        'h-full rounded-full transition-all',
                                        stats.alert_level === 'critical' && 'bg-red-500',
                                        stats.alert_level === 'warning' && 'bg-amber-500',
                                        stats.alert_level === 'normal' && 'bg-primary',
                                    )}
                                    style={{ width: `${Math.min(Number(stats.storage_used_percentage || 0), 100)}%` }}
                                />
                            </div>
                            <div className="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                                <span>Sisa {formatBytes(stats.storage_free_bytes)} ({stats.free_percentage}% kosong)</span>
                                <span>Terpakai fitur file endorse: {formatBytes(stats.total_size)}</span>
                            </div>
                        </div>
                    </section>
                )}

                {stats.storage_available && stats.alert_message && (
                    <div className={cn(
                        'rounded-2xl border px-4 py-3 text-sm',
                        stats.alert_level === 'critical' && 'border-red-200 bg-red-50 text-red-700',
                        stats.alert_level === 'warning' && 'border-amber-200 bg-amber-50 text-amber-700',
                    )}>
                        {stats.alert_message}
                    </div>
                )}

                <EndorsementFileUploadCard
                    defaultEndorsementId={filters.endorsement_id}
                    endorsementOptions={endorsementOptions}
                    maxUploadMb={maxUploadMb}
                    maxFilesPerRequest={maxFilesPerRequest}
                    title="Upload cepat"
                    description="Pilih endorse lalu upload banyak file sekaligus. Cocok untuk pindah file dari iPhone atau Android ke server."
                />

                <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                    <form onSubmit={submitFilters} className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                        <div className="xl:col-span-2">
                            <label className="mb-2 block text-sm font-medium text-foreground">Cari file atau endorse</label>
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <input
                                    className="w-full rounded-xl border border-border py-2.5 pl-10 pr-3 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                    name="q"
                                    onChange={(event) => filterForm.setData('q', event.target.value)}
                                    placeholder="contoh: top coffee, revisi-final.mp4"
                                    value={filterForm.data.q}
                                />
                            </div>
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium text-foreground">Filter endorse</label>
                            <select
                                className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                onChange={(event) => filterForm.setData('endorsement_id', event.target.value)}
                                value={filterForm.data.endorsement_id}
                            >
                                <option value="">Semua endorse</option>
                                {endorsementOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}{option.is_deleted ? ' - dibatalkan' : ''}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium text-foreground">Jenis file</label>
                            <select
                                className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                onChange={(event) => filterForm.setData('category', event.target.value)}
                                value={filterForm.data.category}
                            >
                                <option value="">Semua jenis</option>
                                {categoryOptions.map((option) => (
                                    <option key={option.value} value={option.value}>{option.label}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium text-foreground">Urutkan</label>
                            <select
                                className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                onChange={(event) => filterForm.setData('sort', event.target.value)}
                                value={filterForm.data.sort}
                            >
                                {sortOptions.map((option) => (
                                    <option key={option.value} value={option.value}>{option.label}</option>
                                ))}
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
                                    type="submit"
                                >
                                    Filter
                                </button>
                                <button
                                    className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                                    onClick={resetFilters}
                                    type="button"
                                >
                                    Reset
                                </button>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Menampilkan {files.from ?? 0}-{files.to ?? 0} dari {files.total ?? 0} file
                            </p>
                        </div>
                    </form>
                </section>

                <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-foreground">Daftar file</p>
                            <p className="text-xs text-muted-foreground">
                                Gunakan preview untuk cek isi cepat. Pilih urutan file terbesar supaya lebih gampang bersih-bersih storage.
                            </p>
                        </div>
                    </div>

                    <EndorsementFileBrowser files={files.data} />

                    <div className="mt-4">
                        <Pagination links={files.links} />
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function SummaryCard({ label, value, note }) {
    return (
        <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-foreground">{value}</p>
            <p className="mt-2 text-xs text-muted-foreground">{note}</p>
        </section>
    );
}
