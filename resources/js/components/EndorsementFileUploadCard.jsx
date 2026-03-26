import React, { useId, useMemo, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { AlertCircle, FolderOpen, HardDrive, Upload, Video, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatBytes } from '@/lib/formatters';

const LARGE_VIDEO_BYTES = 100 * 1024 * 1024;

export default function EndorsementFileUploadCard({
    defaultEndorsementId = '',
    endorsementOptions = [],
    fixedEndorsementId = null,
    maxUploadMb = 2048,
    maxFilesPerRequest = 50,
    title = 'Upload file',
    description = 'Simpan foto, video, draft, dan dokumen campaign tanpa kompresi.',
    disabled = false,
    disabledReason = '',
}) {
    const inputId = useId();
    const inputRef = useRef(null);
    const mobileInputRef = useRef(null);
    const selectedFilesRef = useRef([]);
    const prepareRunRef = useRef(0);
    const [dragActive, setDragActive] = useState(false);
    const [isPreparing, setIsPreparing] = useState(false);
    const [filesTrimmed, setFilesTrimmed] = useState(false);
    const [selectedFilesMeta, setSelectedFilesMeta] = useState([]);
    const [selectedEndorsementId, setSelectedEndorsementId] = useState(
        fixedEndorsementId ? String(fixedEndorsementId) : String(defaultEndorsementId || ''),
    );
    const form = useForm({});

    const currentEndorsementId = fixedEndorsementId ? String(fixedEndorsementId) : selectedEndorsementId;
    const currentEndorsement = useMemo(
        () => endorsementOptions.find((option) => String(option.value) === String(currentEndorsementId)),
        [endorsementOptions, currentEndorsementId],
    );
    const uploadDisabled = disabled || !currentEndorsementId || currentEndorsement?.is_deleted;
    const selectedCount = selectedFilesMeta.length;
    const totalSelectedSize = useMemo(
        () => selectedFilesMeta.reduce((total, file) => total + Number(file.size || 0), 0),
        [selectedFilesMeta],
    );
    const visibleFiles = selectedFilesMeta.slice(0, 5);
    const largeVideoCount = selectedFilesMeta.filter((file) => file.isLargeVideo).length;

    const summarizeFile = (file, index) => ({
        id: `${file.name}-${file.size}-${index}`,
        name: file.name,
        size: Number(file.size || 0),
        type: file.type || '',
        isLargeVideo: (file.type || '').startsWith('video/') && Number(file.size || 0) >= LARGE_VIDEO_BYTES,
    });

    const syncFiles = (fileList) => {
        const allFiles = Array.from(fileList || []);
        const trimmedFiles = allFiles.slice(0, maxFilesPerRequest);
        const runId = prepareRunRef.current + 1;

        prepareRunRef.current = runId;
        setIsPreparing(true);
        setFilesTrimmed(allFiles.length > maxFilesPerRequest);
        setSelectedFilesMeta([]);

        window.setTimeout(() => {
            if (prepareRunRef.current !== runId) {
                return;
            }

            selectedFilesRef.current = trimmedFiles;
            setSelectedFilesMeta(trimmedFiles.map(summarizeFile));
            setIsPreparing(false);
        }, 30);
    };

    const resetSelectedFiles = () => {
        prepareRunRef.current += 1;
        selectedFilesRef.current = [];
        setSelectedFilesMeta([]);
        setFilesTrimmed(false);
        setIsPreparing(false);
        if (inputRef.current) {
            inputRef.current.value = '';
        }
        if (mobileInputRef.current) {
            mobileInputRef.current.value = '';
        }
    };

    const submit = (event) => {
        event.preventDefault();

        if (uploadDisabled || selectedFilesRef.current.length === 0 || isPreparing) {
            return;
        }

        form.transform(() => ({
            files: selectedFilesRef.current,
        })).post(`/endorsements/${currentEndorsementId}/files`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                resetSelectedFiles();
                form.reset();
            },
        });
    };

    const removeFile = (index) => {
        const nextFiles = selectedFilesRef.current.filter((_, fileIndex) => fileIndex !== index);
        selectedFilesRef.current = nextFiles;
        setSelectedFilesMeta(nextFiles.map(summarizeFile));
    };

    const onDrop = (event) => {
        event.preventDefault();
        setDragActive(false);
        syncFiles(event.dataTransfer.files);
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
                        <Upload className={cn('h-5 w-5', isPreparing && 'animate-pulse')} />
                    </div>
                    <p className="mt-4 text-sm font-semibold text-foreground">
                        {isPreparing ? 'Menyiapkan file...' : 'Tarik file ke sini atau pilih manual'}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {isPreparing
                            ? 'Browser sedang membaca file yang dipilih. Untuk video besar di iPhone, proses ini memang bisa butuh beberapa saat.'
                            : 'File disimpan apa adanya, tanpa resize, tanpa kompresi, dan kualitas tetap asli.'}
                    </p>
                    <div className="mt-4 flex flex-wrap items-center justify-center gap-2">
                        <label
                            htmlFor={inputId}
                            className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                        >
                            <FolderOpen className="mr-2 h-4 w-4" />
                            Pilih file
                        </label>
                        {selectedCount > 0 && !isPreparing && (
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
                        id={inputId}
                        ref={inputRef}
                        multiple
                        className="sr-only"
                        name="files[]"
                        onChange={(event) => syncFiles(event.target.files)}
                        type="file"
                    />

                    <div className="mt-4 rounded-2xl border border-border bg-white p-3 text-left md:hidden">
                        <label className="mb-2 block text-sm font-medium text-foreground" htmlFor={`${inputId}-mobile`}>
                            Pilih via input iPhone / Android
                        </label>
                        <input
                            id={`${inputId}-mobile`}
                            ref={mobileInputRef}
                            multiple
                            className="block w-full text-sm text-foreground file:mr-3 file:rounded-xl file:border-0 file:bg-muted file:px-3 file:py-2 file:font-semibold file:text-foreground"
                            name="files[]"
                            onChange={(event) => syncFiles(event.target.files)}
                            type="file"
                        />
                        <p className="mt-2 text-xs text-muted-foreground">
                            Jika Safari terasa susah saat pilih file, gunakan input native ini langsung.
                        </p>
                    </div>
                </div>

                {isPreparing && (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        Menyiapkan file... Setelah selesai, daftar ringkas file akan muncul dan tombol upload otomatis aktif.
                    </div>
                )}

                {!isPreparing && selectedCount > 0 && (
                    <div className="rounded-2xl border border-border bg-muted/20 p-3">
                        <div className="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold text-foreground">{selectedCount} file siap diupload</p>
                                <p className="text-xs text-muted-foreground">
                                    Ringkasan dibuat lebih ringan supaya browser HP tidak cepat berat.
                                </p>
                            </div>
                            <div className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs text-muted-foreground">
                                <HardDrive className="h-3.5 w-3.5" />
                                {formatBytes(totalSelectedSize)}
                            </div>
                        </div>

                        {filesTrimmed && (
                            <div className="mb-3 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                                Jumlah file dibatasi ke {maxFilesPerRequest} file per upload. Sisanya tidak ikut diproses.
                            </div>
                        )}

                        {largeVideoCount > 0 && (
                            <div className="mb-3 inline-flex items-center gap-2 rounded-2xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                                <Video className="h-3.5 w-3.5" />
                                {largeVideoCount} video besar terdeteksi. Detailnya disederhanakan agar halaman tetap ringan.
                            </div>
                        )}

                        <div className="mb-3 inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                            <AlertCircle className="h-3.5 w-3.5" />
                            File sudah siap. Tekan tombol upload saat Anda ingin mulai kirim ke server.
                        </div>

                        <div className="space-y-2">
                            {visibleFiles.map((file, index) => (
                                <div key={file.id} className="flex items-center justify-between gap-3 rounded-2xl bg-white px-3 py-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-foreground">{file.name}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {file.isLargeVideo ? 'Video besar - detail dipersingkat' : formatBytes(file.size)}
                                        </p>
                                    </div>
                                    <button
                                        className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-border text-muted-foreground transition hover:bg-muted"
                                        onClick={() => removeFile(index)}
                                        type="button"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                            ))}
                            {selectedCount > visibleFiles.length && (
                                <div className="rounded-2xl border border-dashed border-border px-3 py-3 text-sm text-muted-foreground">
                                    +{selectedCount - visibleFiles.length} file lain sudah siap dan akan ikut diupload.
                                </div>
                            )}
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
                {form.errors.files && (
                    <p className="text-xs text-red-600">{form.errors.files}</p>
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
                        disabled={uploadDisabled || form.processing || selectedCount === 0 || isPreparing}
                        type="submit"
                    >
                        {form.processing ? 'Mengupload...' : `Upload sekarang${selectedCount ? ` (${selectedCount})` : ''}`}
                    </button>
                </div>
            </form>
        </section>
    );
}
