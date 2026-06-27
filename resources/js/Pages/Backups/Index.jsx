import React from 'react';
import { router, useForm } from '@inertiajs/react';
import { CalendarDays, Clock3, Download, FileArchive, Play, RefreshCw, ShieldCheck, TriangleAlert } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import { formatBytes, formatDate } from '@/lib/formatters';

export default function DatabaseBackupsIndex({ setting, dayOptions, logs, summary }) {
    const form = useForm({
        enabled: Boolean(setting.enabled),
        timezone: setting.timezone ?? 'Asia/Jakarta',
        run_time: setting.run_time ?? '01:00',
        run_days: setting.run_days ?? Object.keys(dayOptions),
        start_date: setting.start_date ?? '',
        end_date: setting.end_date ?? '',
        keep_days: String(setting.keep_days ?? 7),
    });

    const submit = (event) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            enabled: data.enabled ? 1 : 0,
            keep_days: Number(data.keep_days),
        })).post('/database-backups/settings', {
            preserveScroll: true,
        });
    };

    const runNow = () => {
        if (!window.confirm('Jalankan backup database sekarang?')) {
            return;
        }

        router.post('/database-backups/run', {}, {
            preserveScroll: true,
        });
    };

    const toggleDay = (day) => {
        const current = new Set(form.data.run_days);

        if (current.has(day)) {
            current.delete(day);
        } else {
            current.add(day);
        }

        const nextDays = Object.keys(dayOptions).filter((value) => current.has(value));
        form.setData('run_days', nextDays);
    };

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p className="text-sm text-muted-foreground">Backup & restore readiness</p>
                        <h1 className="text-2xl font-semibold text-foreground">Backup Database</h1>
                        <p className="text-sm text-muted-foreground">
                            Jalankan backup manual, atur jadwal otomatis, dan unduh file hasil backup dari satu halaman.
                        </p>
                    </div>
                    <button
                        className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                        disabled={form.processing}
                        onClick={runNow}
                        type="button"
                    >
                        <Play className="h-4 w-4" />
                        Backup Sekarang
                    </button>
                </div>

                <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={ShieldCheck}
                        label="Status Jadwal"
                        value={setting.enabled ? 'Aktif' : 'Nonaktif'}
                        helper={setting.enabled ? 'Scheduler siap cek tiap menit.' : 'Backup otomatis belum diaktifkan.'}
                    />
                    <StatCard
                        icon={Clock3}
                        label="Jadwal Berikutnya"
                        value={summary.next_run_at ? formatDateTime(summary.next_run_at) : '-'}
                        helper={setting.enabled ? `Timezone ${setting.timezone}` : 'Aktifkan jadwal untuk melihat run berikutnya.'}
                    />
                    <StatCard
                        icon={FileArchive}
                        label="Backup Berhasil"
                        value={String(summary.success_count ?? 0)}
                        helper={summary.last_success_file ? `File terakhir: ${summary.last_success_file}` : 'Belum ada backup sukses.'}
                    />
                    <StatCard
                        icon={TriangleAlert}
                        label="Backup Gagal"
                        value={String(summary.failed_count ?? 0)}
                        helper={summary.last_success_at ? `Sukses terakhir: ${formatDateTime(summary.last_success_at)}` : 'Belum ada riwayat backup sukses.'}
                    />
                </div>

                <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <div className="mb-4 flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">Pengaturan</p>
                            <h2 className="text-base font-semibold text-foreground">Jadwal Backup Otomatis</h2>
                            <p className="text-sm text-muted-foreground">
                                Simpan sekali, lalu scheduler server akan menjalankan backup mengikuti hari dan jam yang dipilih.
                            </p>
                        </div>
                        <div className="text-xs text-muted-foreground">
                            {setting.updated_at
                                ? `Terakhir diubah ${formatDateTime(setting.updated_at)}${setting.updated_by ? ` oleh ${setting.updated_by}` : ''}`
                                : 'Belum pernah disimpan'}
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-5">
                        <div className="rounded-2xl border border-border bg-muted/20 p-4">
                            <label className="flex items-start gap-3">
                                <input
                                    checked={form.data.enabled}
                                    className="mt-1 h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                    onChange={(event) => form.setData('enabled', event.target.checked)}
                                    type="checkbox"
                                />
                                <span>
                                    <span className="block text-sm font-semibold text-foreground">Aktifkan backup otomatis</span>
                                    <span className="block text-xs text-muted-foreground">
                                        Saat aktif, perintah `php artisan schedule:run` di server akan memeriksa jadwal ini setiap menit.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <Field label="Jam Backup" error={form.errors.run_time}>
                                <Input onChange={(event) => form.setData('run_time', event.target.value)} type="time" value={form.data.run_time} />
                            </Field>
                            <Field label="Timezone" error={form.errors.timezone}>
                                <Select onChange={(event) => form.setData('timezone', event.target.value)} value={form.data.timezone}>
                                    <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
                                    <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                                    <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                                    <option value="UTC">UTC</option>
                                </Select>
                            </Field>
                            <Field label="Simpan Backup" hint="Backup lama akan dibersihkan otomatis." error={form.errors.keep_days}>
                                <Input min="1" max="365" onChange={(event) => form.setData('keep_days', event.target.value)} type="number" value={form.data.keep_days} />
                            </Field>
                        </div>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <Field label="Mulai Berlaku" hint="Opsional" error={form.errors.start_date}>
                                <Input onChange={(event) => form.setData('start_date', event.target.value)} type="date" value={form.data.start_date} />
                            </Field>
                            <Field label="Berakhir Pada" hint="Opsional" error={form.errors.end_date}>
                                <Input onChange={(event) => form.setData('end_date', event.target.value)} type="date" value={form.data.end_date} />
                            </Field>
                        </div>

                        <Field label="Hari Aktif" hint="Pilih minimal satu hari." error={form.errors.run_days || form.errors['run_days.0']}>
                            <div className="grid grid-cols-2 gap-2 md:grid-cols-4 xl:grid-cols-7">
                                {Object.entries(dayOptions).map(([day, label]) => {
                                    const active = form.data.run_days.includes(day);

                                    return (
                                        <button
                                            key={day}
                                            className={`rounded-xl border px-3 py-3 text-sm font-semibold transition ${
                                                active
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-border bg-white text-foreground hover:bg-muted'
                                            }`}
                                            onClick={() => toggleDay(day)}
                                            type="button"
                                        >
                                            {label}
                                        </button>
                                    );
                                })}
                            </div>
                        </Field>

                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="inline-flex items-center gap-2 rounded-xl bg-muted/50 px-3 py-2 text-xs text-muted-foreground">
                                <CalendarDays className="h-4 w-4" />
                                {summary.next_run_at
                                    ? `Backup berikutnya direncanakan ${formatDateTime(summary.next_run_at)}`
                                    : 'Belum ada jadwal berikutnya.'}
                            </div>
                            <button
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                                disabled={form.processing}
                                type="submit"
                            >
                                <RefreshCw className="h-4 w-4" />
                                {form.processing ? 'Menyimpan...' : 'Simpan Jadwal'}
                            </button>
                        </div>
                    </form>
                </section>

                <section className="rounded-3xl border border-border bg-white p-4 shadow-sm">
                    <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">Riwayat eksekusi</p>
                            <h2 className="text-base font-semibold text-foreground">Log Backup</h2>
                        </div>
                        <div className="text-xs text-muted-foreground">
                            File sukses bisa langsung diunduh dari sini.
                        </div>
                    </div>

                    <div className="space-y-3">
                        {logs.data.length === 0 && (
                            <div className="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                                Belum ada log backup. Jalankan backup manual pertama untuk mulai mencatat riwayat.
                            </div>
                        )}

                        {logs.data.map((log) => (
                            <article key={log.id} className="rounded-2xl border border-border p-4">
                                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div className="min-w-0 space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge tone={log.status}>{statusLabel(log.status)}</Badge>
                                            <Badge tone="muted">{log.trigger_type === 'scheduled' ? 'Terjadwal' : 'Manual'}</Badge>
                                            <Badge tone="muted">{String(log.database_driver || '').toUpperCase()}</Badge>
                                            {log.triggered_by && <Badge tone="muted">oleh {log.triggered_by}</Badge>}
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-foreground">
                                                {log.file_name ?? 'Belum ada file backup'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Mulai: {formatDateTime(log.started_at)}
                                                {log.finished_at ? ` | Selesai: ${formatDateTime(log.finished_at)}` : ''}
                                                {log.scheduled_for ? ` | Slot jadwal: ${formatDateTime(log.scheduled_for)}` : ''}
                                            </p>
                                        </div>
                                        <p className="text-sm text-muted-foreground">{log.message || '-'}</p>
                                    </div>

                                    <div className="flex flex-col items-start gap-2 xl:items-end">
                                        <div className="text-sm font-semibold text-foreground">
                                            {log.file_size_bytes > 0 ? formatBytes(log.file_size_bytes) : '-'}
                                        </div>
                                        {log.download_url ? (
                                            <a
                                                className="inline-flex items-center justify-center gap-2 rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                                                href={log.download_url}
                                            >
                                                <Download className="h-4 w-4" />
                                                Download
                                            </a>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">Tidak ada file untuk diunduh</span>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-xs text-muted-foreground">
                            Menampilkan {logs.from ?? 0}-{logs.to ?? 0} dari {logs.total ?? 0} log
                        </p>
                        <Pagination links={logs.links} />
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function StatCard({ icon: Icon, label, value, helper }) {
    return (
        <div className="rounded-3xl border border-border bg-white p-4 shadow-sm">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Icon className="h-4 w-4" />
                {label}
            </div>
            <p className="mt-2 text-xl font-semibold text-foreground">{value}</p>
            <p className="mt-1 text-xs text-muted-foreground">{helper}</p>
        </div>
    );
}

function Field({ label, hint, error, children }) {
    return (
        <div>
            <label className="mb-2 block text-sm font-medium text-foreground">{label}</label>
            {children}
            {hint && <p className="mt-1 text-xs text-muted-foreground">{hint}</p>}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}

function Input(props) {
    return (
        <input
            {...props}
            className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
        />
    );
}

function Select(props) {
    return (
        <select
            {...props}
            className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
        />
    );
}

function Badge({ children, tone }) {
    const toneClasses = {
        success: 'bg-emerald-100 text-emerald-700',
        failed: 'bg-red-100 text-red-700',
        running: 'bg-amber-100 text-amber-700',
        muted: 'bg-muted text-foreground',
    };

    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${toneClasses[tone] ?? toneClasses.muted}`}>
            {children}
        </span>
    );
}

function statusLabel(status) {
    if (status === 'success') {
        return 'Berhasil';
    }

    if (status === 'failed') {
        return 'Gagal';
    }

    if (status === 'running') {
        return 'Berjalan';
    }

    return status;
}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    return formatDate(value, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}
