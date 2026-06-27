import React, { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatDate } from '@/lib/formatters';

function StageProgress({ statusOptions, current }) {
    const keys = Object.keys(statusOptions);
    const currentIndex = keys.indexOf(current);

    return (
        <div className="flex gap-2 overflow-x-auto pb-1">
            {keys.map((key, index) => {
                const active = key === current;
                const done = currentIndex > -1 && index < currentIndex;

                return (
                    <div
                        key={key}
                        className={`shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold ${
                            active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : done
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    : 'border-border bg-white text-muted-foreground'
                        }`}
                    >
                        {index + 1}. {statusOptions[key]}
                    </div>
                );
            })}
        </div>
    );
}

export default function EndorsementsShow({
    endorsement,
    revisions,
    logs,
    statusOptions = {},
    isDeletedView = false,
}) {
    const revisionForm = useForm({
        revision_date: '',
        uploaded_to_drive: false,
        is_approved: false,
        note: '',
    });
    const deleteForm = useForm({
        delete_reason: '',
    });
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    const submitRevision = (event) => {
        event.preventDefault();
        revisionForm.post(`/endorsements/${endorsement.id}/revisions`, {
            preserveScroll: true,
            onSuccess: () => revisionForm.reset(),
        });
    };

    const openDeleteDialog = () => {
        deleteForm.reset();
        deleteForm.clearErrors();
        setDeleteDialogOpen(true);
    };

    const submitDelete = (event) => {
        event.preventDefault();

        if (!deleteForm.data.delete_reason.trim()) {
            deleteForm.setError('delete_reason', 'Alasan pembatalan wajib diisi.');
            return;
        }

        deleteForm
            .transform((data) => ({
                delete_reason: data.delete_reason.trim().slice(0, 500),
            }))
            .delete(`/endorsements/${endorsement.id}`, {
                onSuccess: () => setDeleteDialogOpen(false),
            });
    };

    const destroyRevision = (revisionId) => {
        if (!window.confirm('Hapus revisi ini?')) {
            return;
        }

        router.delete(`/endorsements/${endorsement.id}/revisions/${revisionId}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout>
            <div className="space-y-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold text-foreground">{endorsement.brand_name}</h1>
                            {endorsement.trashed && (
                                <span className="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                    Dibatalkan
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">{endorsement.campaign_name || 'Tanpa nama campaign'}</p>
                    </div>
                    {!endorsement.trashed && (
                        <div className="flex flex-wrap gap-2">
                            <Link href={`/endorsements/${endorsement.id}/edit`} className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                                Edit
                            </Link>
                            <button
                                className="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                                onClick={openDeleteDialog}
                                type="button"
                            >
                                Hapus
                            </button>
                        </div>
                    )}
                </div>

                {endorsement.trashed && (
                    <div className="rounded-3xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                        <p className="font-semibold">Endorse ini sudah dibatalkan.</p>
                        <p className="mt-1">Alasan: <span className="font-semibold">{endorsement.deleted_reason || '-'}</span></p>
                        <p>Dihapus pada: {endorsement.deleted_at ? formatDate(endorsement.deleted_at, { hour: '2-digit', minute: '2-digit' }) : '-'}</p>
                        <p>Dihapus oleh: {endorsement.deleted_by_name || '-'}</p>
                    </div>
                )}

                {!endorsement.trashed && Object.keys(statusOptions).length > 0 && (
                    <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <h2 className="text-base font-semibold text-foreground">Tahap Saat Ini</h2>
                            <span className="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                {endorsement.status_label}
                            </span>
                        </div>
                        <div className="mt-4">
                            <StageProgress statusOptions={statusOptions} current={endorsement.status} />
                        </div>
                    </section>
                )}

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <section className="rounded-3xl border border-border bg-white p-5 shadow-sm xl:col-span-2">
                        <h2 className="text-base font-semibold text-foreground">Ringkasan Campaign</h2>
                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Info label="Platform" value={endorsement.platform_label} />
                            <Info label="Jenis Konten" value={endorsement.content_type_label} />
                            <Info label="Status" value={endorsement.status_label} />
                            <Info label="Deal" value={endorsement.deal_date ? formatDate(endorsement.deal_date) : '-'} />
                            <Info label="Order Produk" value={endorsement.product_ordered_at ? formatDate(endorsement.product_ordered_at) : '-'} />
                            <Info label="Produk Diterima" value={endorsement.product_received_at ? formatDate(endorsement.product_received_at) : '-'} />
                            <Info label="Posting Plan" value={endorsement.posting_date ? formatDate(endorsement.posting_date) : '-'} />
                            <Info label="Sudah Posting" value={endorsement.posted_at ? formatDate(endorsement.posted_at) : '-'} />
                            <Info label="Laporan Jatuh Tempo" value={endorsement.insight_due_at ? formatDate(endorsement.insight_due_at) : '-'} />
                            <Info label="Laporan Terkirim" value={endorsement.insight_sent_at ? formatDate(endorsement.insight_sent_at) : '-'} />
                            <Info label="Upload Drive" value={endorsement.drive_uploaded ? 'Sudah' : 'Belum'} />
                            <Info label="Storyline" value={endorsement.storyline_text} />
                            <Info label="Boostcode" value={endorsement.boostcode_text} />
                        </div>
                    </section>

                    <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-foreground">Keuangan</h2>
                        <div className="mt-4 space-y-3 text-sm">
                            <Info label="Skema" value={endorsement.financial_mode_label} />
                            <Info label="Fee" value={formatCurrency(endorsement.fee_amount)} />
                            <Info label="Reimburse" value={formatCurrency(endorsement.reimburse_amount)} />
                            <Info label="Modal Produk" value={formatCurrency(endorsement.product_cost)} />
                            <Info label="Biaya Lain" value={formatCurrency(endorsement.other_cost)} />
                            <Info label="Pendapatan" value={formatCurrency(endorsement.total_income)} />
                            <Info label="Laba Bersih" value={formatCurrency(endorsement.net_profit)} accent={endorsement.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'} />
                            <Info label="Pembayaran" value={endorsement.payment_status_label} />
                            <Info label="Jatuh Tempo Pembayaran" value={endorsement.payment_due_date ? formatDate(endorsement.payment_due_date) : '-'} />
                            <Info label="Pembayaran Masuk" value={endorsement.payment_received_date ? formatDate(endorsement.payment_received_date) : '-'} />
                            <Info label="Saya beli sendiri" value={endorsement.self_purchase ? 'Ya' : 'Tidak'} />
                            {endorsement.checkout_proof_url && (
                                <div>
                                    <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">Bukti Pembelian / Checkout</p>
                                    <a className="mt-1 inline-flex text-sm font-semibold text-primary hover:underline" href={endorsement.checkout_proof_url} target="_blank" rel="noreferrer">
                                        lihat file
                                    </a>
                                </div>
                            )}
                        </div>
                    </section>
                </div>

                <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-foreground">Catatan</h2>
                    <p className="mt-3 text-sm text-foreground">{endorsement.notes || '-'}</p>
                </section>

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-foreground">Tambah Riwayat Revisi</h2>
                        {isDeletedView ? (
                            <p className="mt-3 text-sm text-muted-foreground">Data sudah dibatalkan sehingga tidak dapat menambah revisi baru.</p>
                        ) : (
                            <form onSubmit={submitRevision} className="mt-4 space-y-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="mb-2 block text-sm font-medium text-foreground">Tanggal Revisi</label>
                                        <input
                                            className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                            onChange={(event) => revisionForm.setData('revision_date', event.target.value)}
                                            type="date"
                                            value={revisionForm.data.revision_date}
                                        />
                                        {revisionForm.errors.revision_date && <p className="mt-1 text-xs text-red-600">{revisionForm.errors.revision_date}</p>}
                                    </div>
                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <label className="flex items-center gap-2 rounded-xl border border-border px-3 py-3 text-sm text-foreground">
                                            <input
                                                checked={revisionForm.data.uploaded_to_drive}
                                                className="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                                onChange={(event) => revisionForm.setData('uploaded_to_drive', event.target.checked)}
                                                type="checkbox"
                                            />
                                            Sudah di Drive
                                        </label>
                                        <label className="flex items-center gap-2 rounded-xl border border-border px-3 py-3 text-sm text-foreground">
                                            <input
                                                checked={revisionForm.data.is_approved}
                                                className="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                                onChange={(event) => revisionForm.setData('is_approved', event.target.checked)}
                                                type="checkbox"
                                            />
                                            Approved
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-foreground">Catatan Revisi</label>
                                    <textarea
                                        className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                                        onChange={(event) => revisionForm.setData('note', event.target.value)}
                                        rows="4"
                                        value={revisionForm.data.note}
                                    />
                                    {revisionForm.errors.note && <p className="mt-1 text-xs text-red-600">{revisionForm.errors.note}</p>}
                                </div>
                                <button
                                    className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                                    disabled={revisionForm.processing}
                                    type="submit"
                                >
                                    {revisionForm.processing ? 'Menyimpan...' : 'Simpan Revisi'}
                                </button>
                            </form>
                        )}
                    </section>

                    <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-foreground">Daftar Revisi</h2>
                        <div className="mt-4 space-y-3">
                            {revisions.length === 0 && <p className="text-sm text-muted-foreground">Belum ada histori revisi.</p>}
                            {revisions.map((revision) => (
                                <article key={revision.id} className="rounded-2xl border border-border p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-foreground">{formatDate(revision.revision_date)}</p>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {revision.uploaded_to_drive ? 'Upload Drive: Ya' : 'Upload Drive: Tidak'} | {revision.is_approved ? 'Disetujui' : 'Belum disetujui'}
                                            </p>
                                        </div>
                                        {!isDeletedView && (
                                            <button
                                                className="text-sm font-semibold text-red-600 hover:underline"
                                                onClick={() => destroyRevision(revision.id)}
                                                type="button"
                                            >
                                                hapus
                                            </button>
                                        )}
                                    </div>
                                    <p className="mt-3 text-sm text-foreground">{revision.note || '-'}</p>
                                </article>
                            ))}
                        </div>
                    </section>
                </div>

                <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-foreground">Log Aktivitas</h2>
                    <div className="mt-4 space-y-3">
                        {logs.length === 0 && <p className="text-sm text-muted-foreground">Belum ada aktivitas.</p>}
                        {logs.map((log) => (
                            <article key={log.id} className="rounded-2xl border border-border p-4">
                                <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <p className="font-semibold text-foreground">{log.action_label}</p>
                                        <div className="mt-2 space-y-1 text-sm text-muted-foreground">
                                            {log.meta_lines.length === 0 && <p>-</p>}
                                            {log.meta_lines.map((line, index) => (
                                                <p key={`${log.id}-${index}`}>{line}</p>
                                            ))}
                                        </div>
                                    </div>
                                    <p className="text-xs text-muted-foreground">{formatDate(log.created_at, { hour: '2-digit', minute: '2-digit' })}</p>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>
            </div>

            <DeleteEndorseModal
                endorsement={endorsement}
                form={deleteForm}
                isOpen={deleteDialogOpen}
                onClose={() => setDeleteDialogOpen(false)}
                onSubmit={submitDelete}
            />
        </AppLayout>
    );
}

function Info({ label, value, accent = '' }) {
    return (
        <div>
            <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">{label}</p>
            <p className={`mt-1 text-sm font-medium text-foreground ${accent}`}>{value}</p>
        </div>
    );
}

function DeleteEndorseModal({ endorsement, form, isOpen, onClose, onSubmit }) {
    if (!isOpen) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/50 px-4">
            <div className="w-full max-w-lg rounded-2xl border border-border bg-white p-5 shadow-2xl">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.14em] text-red-600">Batalkan endorse</p>
                        <h3 className="mt-2 text-lg font-semibold text-foreground">{endorsement.brand_name}</h3>
                        <p className="text-sm text-muted-foreground">{endorsement.campaign_name || 'Tanpa nama campaign'}</p>
                    </div>
                    <button
                        className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border text-muted-foreground transition hover:bg-muted"
                        onClick={onClose}
                        type="button"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <form onSubmit={onSubmit} className="mt-5 space-y-4">
                    <div>
                        <label className="mb-2 block text-sm font-medium text-foreground">Alasan pembatalan</label>
                        <textarea
                            className="min-h-28 w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                            maxLength={500}
                            onChange={(event) => form.setData('delete_reason', event.target.value)}
                            placeholder="Contoh: campaign dibatalkan brand, brief berubah, atau produk tidak jadi dikirim."
                            value={form.data.delete_reason}
                        />
                        <div className="mt-1 flex items-center justify-between gap-3 text-xs">
                            <span className="text-red-600">{form.errors.delete_reason}</span>
                            <span className="text-muted-foreground">{form.data.delete_reason.length}/500</span>
                        </div>
                    </div>

                    <div className="rounded-xl bg-amber-50 px-3 py-3 text-sm text-amber-800">
                        Data akan pindah ke Arsip Dihapus dan alasan pembatalan tersimpan di detail arsip.
                    </div>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                            onClick={onClose}
                            type="button"
                        >
                            Batal
                        </button>
                        <button
                            className="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60"
                            disabled={form.processing || !form.data.delete_reason.trim()}
                            type="submit"
                        >
                            {form.processing ? 'Memindahkan...' : 'Pindahkan ke Arsip'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
