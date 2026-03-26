import React, { useMemo, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { FolderOpen, HardDrive, Upload, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatBytes } from '@/lib/formatters';

export default function EndorsementFileUploadCard({
    defaultEndorsementId = '',
    endorsementOptions = [],
    fixedEndorsementId = null,
    maxUploadMb = 512,
    maxFilesPerRequest = 20,
    title = 'Upload file',
    description = 'Simpan foto, video, draft, dan dokumen campaign tanpa kompresi.',
    disabled = false,
    disabledReason = '',
}) {
    const inputRef = useRef(null);
    const [dragActive, setDragActive] = useState(false);
    const [selectedEndorsementId, setSelectedEndorsementId] = useState(
        fixedEndorsementId ? String(fixedEndorsementId) : String(defaultEndorsementId || ''),
    );
    const form = useForm({
        files: [],
    });

    const currentEndorsementId = fixedEndorsementId ? String(fixedEndorsementId) : selectedEndorsementId;
    const currentEndorsement = useMemo(
        () => endorsementOptions.find((option) => String(option.value) === String(currentEndorsementId)),
        [endorsementOptions, currentEndorsementId],
    );
    const uploadDisabled = disabled || !currentEndorsementId || currentEndorsement?.is_deleted;

    const setFiles = (fileList) => {
        const files = Array.from(fileList || []).slice(0, maxFilesPerRequest);
        form.setData('files', files);
    };

    const resetSelectedFiles = () => {
        form.setData('files', []);
        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    const submit = (event) => {
        event.preventDefault();

        if (uploadDisabled || form.data.files.length === 0) {
            return;
        }

        form.post(`/endorsements/${currentEndorsementId}/files`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                resetSelectedFiles();
            },
        });
    };

    const onDrop = (event) => {
        event.preventDefault();
        setDragActive(false);
        setFiles(event.dataTransfer.files);
    };

    return (
        <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 className="text-base font-semibold text-foreground">{title}</h2>
                    <p className="mt-1 text-sm text-muted-foreground">{description}</p>
                </div>
                <div className="rounded-2xl bg-muted/50 px-3 py-2 text-xs text-muted-foreground">
                    Maks. {maxUploadMb} MB / file
                </div>
            </div>

            <form onSubmit={submit} className="mt-4 space-y-4">
                {!fixedEndorsementId && (
                    <div>
                        <label className="mb-2 block text-sm font-medium text-foreground">Simpan ke endorse</label>
                        <select
                            className="w-full rounded-xl border border-border px-3 py-2.5 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15"
                            onChange={(event) => setSelectedEndorsementId(event.target.value)}
                            value={selectedEndorsementId}
                        >
                            <option value="">Pilih endorse lebih dulu</option>
                            {endorsementOptions.map((option) => (
                                <option key={option.value} value={option.value} disabled={option.is_deleted}>
                                    {option.label}{option.is_deleted ? ' - dibatalkan' : ''}
                                </option>
                            ))}
                        </select>
                    </div>
                )}

                <div
                    className={cn(
                        'rounded-3xl border border-dashed px-4 py-8 text-center transition',
                        dragActive ? 'border-primary bg-primary/5' : 'border-border bg-muted/20',
                    )}
                    onDragEnter={(event) => {
                        event.preventDefault();
                        setDragActive(true);
                    }}
                    onDragLeave={(event) => {
                        event.preventDefault();
                        setDragActive(false);
                    }}
                    onDragOver={(event) => event.preventDefault()}
                    onDrop={onDrop}
                >
                    <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <Upload className="h-5 w-5" />
                    </div>
                    <p className="mt-4 text-sm font-semibold text-foreground">Tarik file ke sini atau pilih manual</p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        File disimpan apa adanya, tanpa resize, tanpa kompresi, dan kualitas tetap asli.
                    </p>
                    <div className="mt-4 flex flex-wrap items-center justify-center gap-2">
                        <button
                            className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                            onClick={() => inputRef.current?.click()}
                            type="button"
                        >
                            <FolderOpen className="mr-2 h-4 w-4" />
                            Pilih file
                        </button>
                        {form.data.files.length > 0 && (
                            <button
                                className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                                onClick={resetSelectedFiles}
                                type="button"
                            >
                                Reset pilihan
                            </button>
                        )}
                    </div>

                    <input
                        ref={inputRef}
                        multiple
                        className="hidden"
                        onChange={(event) => setFiles(event.target.files)}
                        type="file"
                    />
                </div>

                {form.data.files.length > 0 && (
                    <div className="rounded-2xl border border-border bg-muted/20 p-3">
                        <div className="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold text-foreground">Siap diupload</p>
                                <p className="text-xs text-muted-foreground">{form.data.files.length} file dipilih</p>
                            </div>
                            <div className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs text-muted-foreground">
                                <HardDrive className="h-3.5 w-3.5" />
                                {formatBytes(form.data.files.reduce((total, file) => total + Number(file.size || 0), 0))}
                            </div>
                        </div>
                        <div className="space-y-2">
                            {form.data.files.map((file, index) => (
                                <div key={`${file.name}-${index}`} className="flex items-center justify-between gap-3 rounded-2xl bg-white px-3 py-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-foreground">{file.name}</p>
                                        <p className="text-xs text-muted-foreground">{formatBytes(file.size)}</p>
                                    </div>
                                    <button
                                        className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-border text-muted-foreground transition hover:bg-muted"
                                        onClick={() => {
                                            const nextFiles = form.data.files.filter((_, fileIndex) => fileIndex !== index);
                                            form.setData('files', nextFiles);
                                        }}
                                        type="button"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {form.progress && (
                    <div>
                        <div className="mb-2 flex items-center justify-between text-xs text-muted-foreground">
                            <span>Progress upload</span>
                            <span>{form.progress.percentage}%</span>
                        </div>
                        <div className="h-2 rounded-full bg-muted">
                            <div className="h-2 rounded-full bg-primary transition-all" style={{ width: `${form.progress.percentage}%` }} />
                        </div>
                    </div>
                )}

                {disabledReason && (
                    <p className="text-xs text-muted-foreground">{disabledReason}</p>
                )}
                {!fixedEndorsementId && currentEndorsement?.is_deleted && (
                    <p className="text-xs text-amber-700">Upload baru hanya untuk endorse yang masih aktif.</p>
                )}

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-xs text-muted-foreground">
                        Maksimal {maxFilesPerRequest} file per upload. Jika file video besar gagal, biasanya limit server belum dinaikkan.
                    </p>
                    <button
                        className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                        disabled={uploadDisabled || form.processing || form.data.files.length === 0}
                        type="submit"
                    >
                        {form.processing ? 'Mengupload...' : 'Upload sekarang'}
                    </button>
                </div>
            </form>
        </section>
    );
}
