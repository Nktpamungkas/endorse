import React, { useRef } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatCurrencyInput, toCurrencyDigits } from '@/lib/formatters';

const NA_MODES = ['na_dikirim_brand', 'na_tanpa_produk'];
const STATUS_ORDER = [
    'deal_masuk',
    'pembelian_produk',
    'pembuatan_draft',
    'menunggu_draft_ok',
    'revisi',
    'menunggu_posting',
    'menunggu_insight',
    'menunggu_payment',
    'selesai',
];
const STATUS_FIELD_HINTS = {
    deal_masuk: 'Pilih saat deal baru masuk dan campaign mulai dicatat.',
    pembelian_produk: 'Pilih saat produk sedang dibeli atau masih menunggu dikirim.',
    pembuatan_draft: 'Pilih saat konten masih dalam proses dibuat.',
    menunggu_draft_ok: 'Pilih saat draft sudah dikirim dan menunggu persetujuan brand.',
    revisi: 'Pilih saat ada revisi dari brand.',
    menunggu_posting: 'Pilih saat konten sudah siap tayang.',
    menunggu_insight: 'Pilih saat konten sudah tayang dan tinggal kirim laporan.',
    menunggu_payment: 'Pilih saat pekerjaan selesai dan tinggal menunggu pembayaran.',
    selesai: 'Pilih saat campaign sudah selesai.',
};

function FieldError({ message }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-red-600">{message}</p>;
}

function Field({ label, htmlFor, hint, children, error, className }) {
    return (
        <div className={className}>
            <label htmlFor={htmlFor} className="mb-2 block text-sm font-medium text-foreground">
                {label}
            </label>
            {children}
            {hint && <p className="mt-1 text-xs text-muted-foreground">{hint}</p>}
            <FieldError message={error} />
        </div>
    );
}

function Input({ className, ...props }) {
    return (
        <input
            {...props}
            className={cn(
                'w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15',
                className,
            )}
        />
    );
}

function Select({ className, children, ...props }) {
    return (
        <select
            {...props}
            className={cn(
                'w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15',
                className,
            )}
        >
            {children}
        </select>
    );
}

function Textarea({ className, ...props }) {
    return (
        <textarea
            {...props}
            className={cn(
                'w-full rounded-xl border border-border bg-white px-3 py-2.5 text-sm text-foreground outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15',
                className,
            )}
        />
    );
}

function Checkbox({ label, checked, onChange, name }) {
    return (
        <label className="flex items-start gap-3 rounded-xl border border-border bg-white px-3 py-3 text-sm text-foreground">
            <input
                checked={checked}
                className="mt-1 h-4 w-4 rounded border-border text-primary focus:ring-primary"
                name={name}
                onChange={(event) => onChange(event.target.checked)}
                type="checkbox"
            />
            <span>{label}</span>
        </label>
    );
}

function CurrencyField({ label, name, value, onChange, error, hint, disabled, className }) {
    return (
        <Field label={label} htmlFor={`${name}_display`} hint={hint} error={error} className={className}>
            <div className="flex overflow-hidden rounded-xl border border-border bg-white focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/15">
                <span className="border-r border-border bg-muted px-3 py-2.5 text-sm text-muted-foreground">Rp</span>
                <Input
                    id={`${name}_display`}
                    name={name}
                    className="rounded-none border-0 focus:ring-0"
                    disabled={disabled}
                    inputMode="numeric"
                    onChange={(event) => onChange(toCurrencyDigits(event.target.value))}
                    placeholder="0"
                    value={formatCurrencyInput(value)}
                />
            </div>
        </Field>
    );
}

function addDaysISODate(value, days) {
    if (!value) {
        return '';
    }

    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    date.setDate(date.getDate() + days);

    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

function getTodayISODate() {
    const date = new Date();

    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

function liftStatus(current, target) {
    const currentRank = STATUS_ORDER.indexOf(current);
    const targetRank = STATUS_ORDER.indexOf(target);

    if (targetRank === -1) {
        return current;
    }

    if (currentRank === -1 || currentRank < targetRank) {
        return target;
    }

    return current;
}

function amount(value) {
    return Number(toCurrencyDigits(value) || 0);
}

function SummaryMetric({ label, value, accent }) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className={cn('mt-1 text-base font-semibold text-foreground', accent)}>{value}</p>
        </div>
    );
}

export default function EndorsementForm({
    endorsement,
    statusOptions,
    platformOptions,
    contentTypeOptions,
    financialModeOptions,
    paymentStatusOptions,
    submitLabel,
    mode = 'create',
}) {
    const lastManualFinancialMode = useRef(
        endorsement.financial_mode && !NA_MODES.includes(endorsement.financial_mode)
            ? endorsement.financial_mode
            : 'reimburse_duluan',
    );
    const csrfToken = typeof document !== 'undefined'
        ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        : '';

    const form = useForm({
        brand_name: endorsement.brand_name ?? '',
        campaign_name: endorsement.campaign_name ?? '',
        platform: endorsement.platform ?? 'tiktok',
        content_type: endorsement.content_type ?? 'video',
        status: endorsement.status ?? 'deal_masuk',
        deal_date: endorsement.deal_date ?? '',
        product_ordered_at: endorsement.product_ordered_at ?? '',
        product_received_at: endorsement.product_received_at ?? '',
        draft_deadline: endorsement.draft_deadline ?? '',
        storyline_required: Boolean(endorsement.storyline_required),
        storyline_done: Boolean(endorsement.storyline_done),
        drive_uploaded: Boolean(endorsement.drive_uploaded),
        approved_at: endorsement.approved_at ?? '',
        posting_date: endorsement.posting_date ?? '',
        posted_at: endorsement.posted_at ?? '',
        insight_due_at: endorsement.insight_due_at ?? '',
        insight_sent_at: endorsement.insight_sent_at ?? '',
        boostcode_required: Boolean(endorsement.boostcode_required),
        boostcode_duration_days: endorsement.boostcode_duration_days ?? '',
        self_purchase: Boolean(endorsement.self_purchase),
        checkout_proof: null,
        financial_mode: endorsement.financial_mode ?? 'reimburse_duluan',
        fee_amount: toCurrencyDigits(endorsement.fee_amount),
        reimburse_amount: toCurrencyDigits(endorsement.reimburse_amount),
        product_cost: toCurrencyDigits(endorsement.product_cost),
        other_cost: toCurrencyDigits(endorsement.other_cost),
        payment_status: endorsement.payment_status ?? 'belum_bayar',
        payment_due_date: endorsement.payment_due_date ?? '',
        payment_received_date: endorsement.payment_received_date ?? '',
        notes: endorsement.notes ?? '',
    });

    const isEdit = mode === 'edit';
    const reimburseLocked = !form.data.self_purchase || ['reimburse_bersama_fee', 'free_barter', ...NA_MODES].includes(form.data.financial_mode);
    const productLocked = !form.data.self_purchase;
    const checkoutDisabled = !form.data.self_purchase;
    const actionUrl = isEdit ? `/endorsements/${endorsement.id}` : '/endorsements';
    const totalIncome = amount(form.data.fee_amount) + amount(form.data.reimburse_amount);
    const totalCost = amount(form.data.product_cost) + amount(form.data.other_cost);
    const netProfit = totalIncome - totalCost;

    const setData = (key, value) => form.setData(key, value);

    const applyStatusFromField = (key, value, targetStatus) => {
        form.setData((data) => ({
            ...data,
            [key]: value,
            status: value ? liftStatus(data.status, targetStatus) : data.status,
        }));
    };

    const handleProductOrderedChange = (value) => {
        applyStatusFromField('product_ordered_at', value, 'pembelian_produk');
    };

    const handleProductReceivedChange = (value) => {
        applyStatusFromField('product_received_at', value, 'pembuatan_draft');
    };

    const handleStorylineDoneChange = (checked) => {
        form.setData((data) => ({
            ...data,
            storyline_done: checked,
            status: checked ? liftStatus(data.status, 'pembuatan_draft') : data.status,
        }));
    };

    const handleDriveUploadedChange = (checked) => {
        form.setData((data) => ({
            ...data,
            drive_uploaded: checked,
            status: checked ? liftStatus(data.status, 'menunggu_draft_ok') : data.status,
        }));
    };

    const handleApprovedAtChange = (value) => {
        applyStatusFromField('approved_at', value, 'menunggu_posting');
    };

    const handlePostedAtChange = (value) => {
        form.setData((data) => ({
            ...data,
            posted_at: value,
            insight_due_at: value && !data.insight_due_at ? addDaysISODate(value, 7) : data.insight_due_at,
            status: value ? liftStatus(data.status, 'menunggu_insight') : data.status,
        }));
    };

    const handleInsightSentChange = (value) => {
        form.setData((data) => ({
            ...data,
            insight_sent_at: value,
            payment_due_date: value && !data.payment_due_date ? addDaysISODate(value, 14) : data.payment_due_date,
            status: value ? liftStatus(data.status, 'menunggu_payment') : data.status,
        }));
    };

    const handlePaymentStatusChange = (value) => {
        form.setData((data) => ({
            ...data,
            payment_status: value,
            payment_received_date: value === 'lunas' && !data.payment_received_date ? getTodayISODate() : data.payment_received_date,
            status: value === 'lunas' ? 'selesai' : data.status,
        }));
    };

    const handlePaymentReceivedChange = (value) => {
        form.setData((data) => ({
            ...data,
            payment_received_date: value,
            payment_status: value ? 'lunas' : data.payment_status,
            status: value ? 'selesai' : data.status,
        }));
    };

    const handleSelfPurchaseChange = (checked) => {
        const next = { self_purchase: checked };

        if (!checked) {
            if (!NA_MODES.includes(form.data.financial_mode)) {
                next.financial_mode = 'na_dikirim_brand';
            }
            next.reimburse_amount = '';
            next.product_cost = '';
            next.checkout_proof = null;
        } else if (NA_MODES.includes(form.data.financial_mode)) {
            next.financial_mode = lastManualFinancialMode.current || 'reimburse_duluan';
        }

        form.setData((data) => ({ ...data, ...next }));
    };

    const handleFinancialModeChange = (value) => {
        let nextValue = value;

        if (!form.data.self_purchase && !NA_MODES.includes(value)) {
            nextValue = 'na_dikirim_brand';
        }

        if (!NA_MODES.includes(nextValue)) {
            lastManualFinancialMode.current = nextValue;
        }

        form.setData((data) => ({
            ...data,
            financial_mode: nextValue,
            reimburse_amount: nextValue === 'reimburse_duluan' ? data.reimburse_amount : '',
            fee_amount: nextValue === 'free_barter' ? '' : data.fee_amount,
        }));
    };

    const submitData = () => {
        const options = {
            forceFormData: true,
            preserveScroll: true,
        };

        if (isEdit) {
            form.transform((data) => ({
                ...data,
                _method: 'put',
            }));
            form.post(`/endorsements/${endorsement.id}`, options);

            return;
        }

        form.post('/endorsements', options);
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        submitData();
    };

    return (
        <form action={actionUrl} encType="multipart/form-data" method="POST" onSubmit={handleSubmit} className="space-y-5 pb-6">
            <input type="hidden" name="_token" value={csrfToken} />
            {isEdit && <input type="hidden" name="_method" value="put" />}
            <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <h2 className="text-base font-semibold text-foreground">Informasi Campaign</h2>
                <p className="mt-1 text-sm text-muted-foreground">Isi identitas campaign dan tahap pekerjaan saat ini.</p>
                <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Field label="Nama Brand *" htmlFor="brand_name" error={form.errors.brand_name} className="xl:col-span-2">
                        <Input id="brand_name" name="brand_name" onChange={(event) => setData('brand_name', event.target.value)} placeholder="Contoh: Wardah" value={form.data.brand_name} />
                    </Field>
                    <Field label="Nama Campaign" htmlFor="campaign_name" error={form.errors.campaign_name} className="xl:col-span-2">
                        <Input id="campaign_name" name="campaign_name" onChange={(event) => setData('campaign_name', event.target.value)} placeholder="Contoh: Launching serum April" value={form.data.campaign_name} />
                    </Field>
                    <Field label="Platform *" htmlFor="platform" error={form.errors.platform}>
                        <Select id="platform" name="platform" onChange={(event) => setData('platform', event.target.value)} value={form.data.platform}>
                            {Object.entries(platformOptions).map(([key, label]) => (
                                <option key={key} value={key}>{label}</option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Jenis Konten *" htmlFor="content_type" error={form.errors.content_type}>
                        <Select id="content_type" name="content_type" onChange={(event) => setData('content_type', event.target.value)} value={form.data.content_type}>
                            {Object.entries(contentTypeOptions).map(([key, label]) => (
                                <option key={key} value={key}>{label}</option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Status *" htmlFor="status" error={form.errors.status} hint={STATUS_FIELD_HINTS[form.data.status]}>
                        <Select id="status" name="status" onChange={(event) => setData('status', event.target.value)} value={form.data.status}>
                            {Object.entries(statusOptions).map(([key, label]) => (
                                <option key={key} value={key}>{label}</option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Tanggal Deal" htmlFor="deal_date" error={form.errors.deal_date}>
                        <Input id="deal_date" name="deal_date" onChange={(event) => setData('deal_date', event.target.value)} type="date" value={form.data.deal_date} />
                    </Field>
                    <Field label="Order Produk" htmlFor="product_ordered_at" error={form.errors.product_ordered_at}>
                        <Input id="product_ordered_at" name="product_ordered_at" onChange={(event) => handleProductOrderedChange(event.target.value)} type="date" value={form.data.product_ordered_at} />
                    </Field>
                    <Field label="Produk Diterima" htmlFor="product_received_at" error={form.errors.product_received_at}>
                        <Input id="product_received_at" name="product_received_at" onChange={(event) => handleProductReceivedChange(event.target.value)} type="date" value={form.data.product_received_at} />
                    </Field>
                    <Field label="Deadline Draft" htmlFor="draft_deadline" error={form.errors.draft_deadline}>
                        <Input id="draft_deadline" name="draft_deadline" onChange={(event) => setData('draft_deadline', event.target.value)} type="date" value={form.data.draft_deadline} />
                    </Field>
                </div>
            </section>

            <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <h2 className="text-base font-semibold text-foreground">Checklist Pekerjaan</h2>
                <p className="mt-1 text-sm text-muted-foreground">Centang dan isi tanggal yang membantu memantau progress konten.</p>
                <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Checkbox label="Perlu storyline dulu" checked={form.data.storyline_required} name="storyline_required" onChange={(checked) => setData('storyline_required', checked)} />
                    <Checkbox label="Storyline sudah selesai" checked={form.data.storyline_done} name="storyline_done" onChange={handleStorylineDoneChange} />
                    <Checkbox label="Draft/revisi sudah di Google Drive" checked={form.data.drive_uploaded} name="drive_uploaded" onChange={handleDriveUploadedChange} />
                    <Checkbox label="Brand minta boostcode" checked={form.data.boostcode_required} name="boostcode_required" onChange={(checked) => setData('boostcode_required', checked)} />

                    <Field label="Tanggal Approved" htmlFor="approved_at" error={form.errors.approved_at}>
                        <Input id="approved_at" name="approved_at" onChange={(event) => handleApprovedAtChange(event.target.value)} type="date" value={form.data.approved_at} />
                    </Field>
                    <Field label="Tanggal Posting (Rencana)" htmlFor="posting_date" error={form.errors.posting_date}>
                        <Input id="posting_date" name="posting_date" onChange={(event) => setData('posting_date', event.target.value)} type="date" value={form.data.posting_date} />
                    </Field>
                    <Field label="Tanggal Sudah Posting" htmlFor="posted_at" error={form.errors.posted_at} hint="Opsional. Isi jika konten sudah tayang.">
                        <Input id="posted_at" name="posted_at" onChange={(event) => handlePostedAtChange(event.target.value)} type="date" value={form.data.posted_at} />
                    </Field>
                    <Field label="Laporan Jatuh Tempo" htmlFor="insight_due_at" error={form.errors.insight_due_at} hint="Isi jika brand memang minta laporan.">
                        <Input id="insight_due_at" name="insight_due_at" onChange={(event) => setData('insight_due_at', event.target.value)} type="date" value={form.data.insight_due_at} />
                    </Field>
                    <Field label="Tanggal Kirim Laporan" htmlFor="insight_sent_at" error={form.errors.insight_sent_at}>
                        <Input id="insight_sent_at" name="insight_sent_at" onChange={(event) => handleInsightSentChange(event.target.value)} type="date" value={form.data.insight_sent_at} />
                    </Field>
                    <Field
                        label="Durasi Boostcode (hari)"
                        htmlFor="boostcode_duration_days"
                        error={form.errors.boostcode_duration_days}
                        hint="Wajib saat boostcode dicentang."
                    >
                        <Input
                            id="boostcode_duration_days"
                            name="boostcode_duration_days"
                            min="7"
                            max="365"
                            onChange={(event) => setData('boostcode_duration_days', event.target.value)}
                            type="number"
                            value={form.data.boostcode_duration_days}
                        />
                    </Field>
                </div>
            </section>

            <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <h2 className="text-base font-semibold text-foreground">Keuangan</h2>
                <p className="mt-1 text-sm text-muted-foreground">Catat fee, reimburse, modal, dan status pembayaran agar ringkasan tetap akurat.</p>
                <div className="mt-4 grid grid-cols-1 gap-3 rounded-xl bg-muted/30 p-3 sm:grid-cols-3">
                    <SummaryMetric label="Pendapatan" value={formatCurrency(totalIncome)} />
                    <SummaryMetric label="Modal" value={formatCurrency(totalCost)} />
                    <SummaryMetric
                        label="Estimasi Laba"
                        value={formatCurrency(netProfit)}
                        accent={netProfit >= 0 ? 'text-emerald-600' : 'text-red-600'}
                    />
                </div>
                <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Field
                        label="Skema Finansial *"
                        htmlFor="financial_mode"
                        error={form.errors.financial_mode}
                        hint={!form.data.self_purchase ? 'Saat produk tidak dibeli sendiri, pilihan aktif hanya mode N/A.' : ''}
                    >
                        <Select id="financial_mode" name="financial_mode" onChange={(event) => handleFinancialModeChange(event.target.value)} value={form.data.financial_mode}>
                            {Object.entries(financialModeOptions).map(([key, label]) => (
                                <option key={key} value={key} disabled={!form.data.self_purchase && !NA_MODES.includes(key)}>{label}</option>
                            ))}
                        </Select>
                    </Field>
                    <CurrencyField
                        label="Fee"
                        name="fee_amount"
                        value={form.data.fee_amount}
                        onChange={(value) => setData('fee_amount', value)}
                        error={form.errors.fee_amount}
                    />
                    <CurrencyField
                        label="Nominal Reimburse"
                        name="reimburse_amount"
                        value={form.data.reimburse_amount}
                        onChange={(value) => setData('reimburse_amount', value)}
                        error={form.errors.reimburse_amount}
                        disabled={reimburseLocked}
                        hint={form.data.financial_mode === 'reimburse_duluan' ? 'Wajib isi nominal > 0 untuk skema ini.' : 'Akan otomatis 0 untuk skema lain.'}
                    />
                    <Checkbox label="Saya beli produk sendiri" checked={form.data.self_purchase} name="self_purchase" onChange={handleSelfPurchaseChange} />
                    <CurrencyField
                        label="Modal Produk"
                        name="product_cost"
                        value={form.data.product_cost}
                        onChange={(value) => setData('product_cost', value)}
                        error={form.errors.product_cost}
                        disabled={productLocked}
                    />
                    <CurrencyField
                        label="Biaya Lain"
                        name="other_cost"
                        value={form.data.other_cost}
                        onChange={(value) => setData('other_cost', value)}
                        error={form.errors.other_cost}
                    />
                    <Field label="Bukti Pembelian / Checkout" htmlFor="checkout_proof" error={form.errors.checkout_proof} hint="JPG, PNG, WEBP, atau PDF.">
                        <Input
                            id="checkout_proof"
                            disabled={checkoutDisabled}
                            name="checkout_proof"
                            onChange={(event) => setData('checkout_proof', event.target.files?.[0] ?? null)}
                            type="file"
                        />
                        {isEdit && endorsement.checkout_proof_url && (
                            <p className="mt-2 text-xs text-muted-foreground">
                                File saat ini:{' '}
                                <a className="font-semibold text-primary hover:underline" href={endorsement.checkout_proof_url} target="_blank" rel="noreferrer">
                                    lihat file
                                </a>
                            </p>
                        )}
                    </Field>
                    <Field label="Status Pembayaran *" htmlFor="payment_status" error={form.errors.payment_status}>
                        <Select id="payment_status" name="payment_status" onChange={(event) => handlePaymentStatusChange(event.target.value)} value={form.data.payment_status}>
                            {Object.entries(paymentStatusOptions).map(([key, label]) => (
                                <option key={key} value={key}>{label}</option>
                            ))}
                        </Select>
                    </Field>
                    <Field label="Jatuh Tempo Payment" htmlFor="payment_due_date" error={form.errors.payment_due_date}>
                        <Input id="payment_due_date" name="payment_due_date" onChange={(event) => setData('payment_due_date', event.target.value)} type="date" value={form.data.payment_due_date} />
                    </Field>
                    <Field label="Tanggal Payment Masuk" htmlFor="payment_received_date" error={form.errors.payment_received_date}>
                        <Input id="payment_received_date" name="payment_received_date" onChange={(event) => handlePaymentReceivedChange(event.target.value)} type="date" value={form.data.payment_received_date} />
                    </Field>
                    <Field label="Catatan" htmlFor="notes" error={form.errors.notes} className="md:col-span-2 xl:col-span-4">
                        <Textarea id="notes" name="notes" onChange={(event) => setData('notes', event.target.value)} placeholder="Contoh: brief khusus, PIC brand, atau catatan revisi." rows="4" value={form.data.notes} />
                    </Field>
                </div>
            </section>

            <div className="flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:justify-end">
                <Link href="/endorsements" className="inline-flex items-center justify-center rounded-xl border border-border px-4 py-2.5 text-sm font-semibold text-foreground transition hover:bg-muted">
                    Batal
                </Link>
                <button
                    className="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                    disabled={form.processing}
                    type="submit"
                >
                    {form.processing ? 'Sedang menyimpan...' : submitLabel}
                </button>
            </div>
        </form>
    );
}
