import React, { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Archive,
    Download,
    Eye,
    ExternalLink,
    File,
    FileText,
    ImageIcon,
    Music,
    Trash2,
    Video,
    X,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatBytes, formatDate } from '@/lib/formatters';

export default function EndorsementFileBrowser({
    files = [],
    compact = false,
    emptyMessage = 'Belum ada file tersimpan.',
    showEndorsement = true,
    allowDelete = true,
}) {
    const [previewing, setPreviewing] = useState(null);

    useEffect(() => {
        if (!previewing) {
            return undefined;
        }

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setPreviewing(null);
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [previewing]);

    const hasFiles = files.length > 0;

    const confirmDelete = (file) => {
        if (!window.confirm(`Hapus file "${file.original_name}" dari penyimpanan?`)) {
            return;
        }

        router.delete(file.delete_url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            {!hasFiles && (
                <div className="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground">
                    {emptyMessage}
                </div>
            )}

            {hasFiles && compact && (
                <div className="grid gap-3 md:grid-cols-2">
                    {files.map((file) => (
                        <article key={file.id} className="rounded-2xl border border-border bg-white p-4 shadow-sm">
                            <div className="flex items-start gap-3">
                                <div className="rounded-2xl bg-muted p-3 text-muted-foreground">
                                    <FileTypeIcon category={file.category} />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold text-foreground">{file.original_name}</p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {file.category_label} · {formatBytes(file.size_bytes)}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">{formatDate(file.uploaded_at, { hour: '2-digit', minute: '2-digit' })}</p>
                                </div>
                            </div>

                            {showEndorsement && (
                                <div className="mt-3 rounded-2xl bg-muted/40 px-3 py-3 text-sm">
                                    <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">Endorse</p>
                                    <p className="mt-1 font-medium text-foreground">{file.endorsement_label}</p>
                                </div>
                            )}

                            <div className="mt-4 grid grid-cols-2 gap-2">
                                {file.can_preview ? (
                                    <button
                                        className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                                        onClick={() => setPreviewing(file)}
                                        type="button"
                                    >
                                        <Eye className="mr-2 h-4 w-4" />
                                        Lihat
                                    </button>
                                ) : (
                                    <a
                                        href={file.download_url}
                                        className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                                    >
                                        <ExternalLink className="mr-2 h-4 w-4" />
                                        Buka
                                    </a>
                                )}
                                <a
                                    href={file.download_url}
                                    className="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                                >
                                    <Download className="mr-2 h-4 w-4" />
                                    Download
                                </a>
                            </div>
                            {allowDelete && (
                                <button
                                    className="mt-2 inline-flex w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                                    onClick={() => confirmDelete(file)}
                                    type="button"
                                >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Hapus
                                </button>
                            )}
                        </article>
                    ))}
                </div>
            )}

            {hasFiles && !compact && (
                <>
                    <div className="hidden overflow-x-auto lg:block">
                        <table className="min-w-full text-sm">
                            <thead className="border-b border-border text-left text-muted-foreground">
                                <tr>
                                    <th className="py-3 pr-4">File</th>
                                    {showEndorsement && <th className="py-3 pr-4">Endorse</th>}
                                    <th className="py-3 pr-4">Tipe</th>
                                    <th className="py-3 pr-4">Ukuran</th>
                                    <th className="py-3 pr-4">Upload</th>
                                    <th className="py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {files.map((file) => (
                                    <tr key={file.id} className="transition hover:bg-muted/30">
                                        <td className="py-3 pr-4">
                                            <div className="flex items-center gap-3">
                                                <div className="rounded-2xl bg-muted p-3 text-muted-foreground">
                                                    <FileTypeIcon category={file.category} />
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="truncate font-semibold text-foreground">{file.original_name}</p>
                                                    <p className="text-xs text-muted-foreground">{file.mime_type || 'application/octet-stream'}</p>
                                                </div>
                                            </div>
                                        </td>
                                        {showEndorsement && (
                                            <td className="py-3 pr-4">
                                                <div className="font-medium text-foreground">{file.endorsement_brand_name || '-'}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {file.endorsement_campaign_name || 'Tanpa campaign'}
                                                    {file.endorsement_deleted ? ' · dibatalkan' : ''}
                                                </div>
                                            </td>
                                        )}
                                        <td className="py-3 pr-4 whitespace-nowrap">{file.category_label}</td>
                                        <td className="py-3 pr-4 whitespace-nowrap">{formatBytes(file.size_bytes)}</td>
                                        <td className="py-3 pr-4 whitespace-nowrap">{formatDate(file.uploaded_at, { hour: '2-digit', minute: '2-digit' })}</td>
                                        <td className="py-3 text-right whitespace-nowrap">
                                            <div className="inline-flex items-center gap-2">
                                                {file.can_preview ? (
                                                    <button
                                                        className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-xs font-semibold text-foreground transition hover:bg-muted"
                                                        onClick={() => setPreviewing(file)}
                                                        type="button"
                                                    >
                                                        <Eye className="mr-2 h-4 w-4" />
                                                        Lihat
                                                    </button>
                                                ) : (
                                                    <a
                                                        href={file.download_url}
                                                        className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-xs font-semibold text-foreground transition hover:bg-muted"
                                                    >
                                                        <ExternalLink className="mr-2 h-4 w-4" />
                                                        Buka
                                                    </a>
                                                )}
                                                <a
                                                    href={file.download_url}
                                                    className="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                                                >
                                                    <Download className="mr-2 h-4 w-4" />
                                                    Download
                                                </a>
                                                {allowDelete && (
                                                    <button
                                                        className="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                                        onClick={() => confirmDelete(file)}
                                                        type="button"
                                                    >
                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                        Hapus
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="grid gap-3 lg:hidden">
                        {files.map((file) => (
                            <article key={file.id} className="rounded-2xl border border-border bg-white p-4 shadow-sm">
                                <div className="flex items-start gap-3">
                                    <div className="rounded-2xl bg-muted p-3 text-muted-foreground">
                                        <FileTypeIcon category={file.category} />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-semibold text-foreground">{file.original_name}</p>
                                        <p className="mt-1 text-xs text-muted-foreground">{file.category_label} · {formatBytes(file.size_bytes)}</p>
                                        <p className="mt-1 text-xs text-muted-foreground">{formatDate(file.uploaded_at, { hour: '2-digit', minute: '2-digit' })}</p>
                                    </div>
                                </div>
                                {showEndorsement && (
                                    <div className="mt-3 rounded-2xl bg-muted/40 px-3 py-3 text-sm">
                                        <p className="text-xs uppercase tracking-[0.12em] text-muted-foreground">Endorse</p>
                                        <p className="mt-1 font-medium text-foreground">{file.endorsement_label}</p>
                                    </div>
                                )}
                                <div className="mt-4 grid grid-cols-2 gap-2">
                                    {file.can_preview ? (
                                        <button
                                            className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                                            onClick={() => setPreviewing(file)}
                                            type="button"
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Lihat
                                        </button>
                                    ) : (
                                        <a
                                            href={file.download_url}
                                            className="inline-flex items-center justify-center rounded-xl border border-border px-3 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                                        >
                                            <ExternalLink className="mr-2 h-4 w-4" />
                                            Buka
                                        </a>
                                    )}
                                    <a
                                        href={file.download_url}
                                        className="inline-flex items-center justify-center rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                                    >
                                        <Download className="mr-2 h-4 w-4" />
                                        Download
                                    </a>
                                </div>
                                {allowDelete && (
                                    <button
                                        className="mt-2 inline-flex w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                                        onClick={() => confirmDelete(file)}
                                        type="button"
                                    >
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Hapus
                                    </button>
                                )}
                            </article>
                        ))}
                    </div>
                </>
            )}

            <PreviewModal file={previewing} onClose={() => setPreviewing(null)} />
        </>
    );
}

function PreviewModal({ file, onClose }) {
    const previewContent = useMemo(() => {
        if (!file) {
            return null;
        }

        if (file.category === 'image') {
            return <img src={file.preview_url} alt={file.original_name} className="max-h-[70vh] w-full rounded-2xl object-contain bg-muted/30" />;
        }

        if (file.category === 'video') {
            return <video src={file.preview_url} controls className="max-h-[70vh] w-full rounded-2xl bg-black" />;
        }

        if (file.category === 'audio') {
            return (
                <div className="rounded-2xl border border-border bg-muted/30 p-8">
                    <audio src={file.preview_url} controls className="w-full" />
                </div>
            );
        }

        if (file.category === 'pdf') {
            return <iframe src={file.preview_url} title={file.original_name} className="h-[70vh] w-full rounded-2xl border border-border bg-white" />;
        }

        return (
            <div className="rounded-2xl border border-dashed border-border bg-muted/20 p-8 text-center">
                <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                    <File className="h-6 w-6" />
                </div>
                <p className="mt-4 text-sm font-semibold text-foreground">Preview langsung belum tersedia</p>
                <p className="mt-1 text-sm text-muted-foreground">File jenis ini tetap aman disimpan apa adanya dan bisa didownload kapan saja.</p>
            </div>
        );
    }, [file]);

    if (!file) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/60 p-4">
            <div className="max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl">
                <div className="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
                    <div className="min-w-0">
                        <p className="truncate text-base font-semibold text-foreground">{file.original_name}</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {file.category_label} · {formatBytes(file.size_bytes)}
                        </p>
                    </div>
                    <button
                        className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-border text-foreground transition hover:bg-muted"
                        onClick={onClose}
                        type="button"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <div className="max-h-[calc(92vh-132px)] overflow-auto px-5 py-5">
                    {previewContent}
                </div>

                <div className="flex flex-col gap-2 border-t border-border px-5 py-4 sm:flex-row sm:justify-end">
                    <a
                        href={file.preview_url}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted"
                    >
                        <ExternalLink className="mr-2 h-4 w-4" />
                        Buka tab baru
                    </a>
                    <a
                        href={file.download_url}
                        className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                    >
                        <Download className="mr-2 h-4 w-4" />
                        Download asli
                    </a>
                </div>
            </div>
        </div>
    );
}

function FileTypeIcon({ category, className }) {
    const Icon = {
        image: ImageIcon,
        video: Video,
        audio: Music,
        pdf: FileText,
        document: FileText,
        archive: Archive,
        other: File,
    }[category] || File;

    return <Icon className={cn('h-5 w-5', className)} />;
}
