import React, { useRef } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatCurrencyInput, toCurrencyDigits } from '@/lib/formatters';

const NA_MODES = ['na_dikirim_brand', 'na_tanpa_produk'];
// Hanya mode reimburse yang butuh self_purchase (beli produk sendiri dulu)
const REQUIRES_SELF_PURCHASE = ['reimburse_duluan', 'reimburse_bersama_fee'];
const STATUS_FIELD_HINTS = {
    deal_masuk: 'Deal baru masuk dan campaign mulai dicatat.',
    pembelian_produk: 'Produk sedang dibeli atau menunggu dikirim.',
    pembuatan_draft: 'Konten masih dalam proses dibuat.',
    menunggu_draft_ok: 'Draft sudah dikirim, menunggu persetujuan brand.',
    revisi: 'Ada revisi dari brand.',
    menunggu_posting: 'Konten sudah siap tayang.',
    menunggu_insight: 'Konten sudah tayang, tinggal kirim laporan.',
    menunggu_payment: 'Pekerjaan selesai, tinggal menunggu pembayaran.',
    selesai: 'Campaign sudah selesai.',
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

function Checkbox({ label, checked, onChange, name, highlight }) {
    return (
        <label className={cn('flex items-start gap-3 rounded-xl border px-3 py-3 text-sm text-foreground', highlight ? 'border-amber-400 bg-amber-50' : 'border-border bg-white')}>
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

// Selektor tahap (status) ala Kanban — klik untuk pilih, TANPA auto-inferensi dari field lain.
function StageStrip({ statusOptions, value, onChange }) {
    const keys = Object.keys(statusOptions);
    const currentIndex = keys.indexOf(value);

    return (
        <div className="flex gap-2 overflow-x-auto pb-1">
            {keys.map((key, index) => {
                const active = key === value;
                const done = currentIndex > -1 && index < currentIndex;

                return (
                    <button
                        key={key}
                        type="button"
                        onClick={() => onChange(key)}
                        className={cn(
                            'shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold transition',
                            active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : done
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                    : 'border-border bg-white text-muted-foreground hover:bg-muted',
                        )}
                    >
                        {index + 1}. {statusOptions[key]}
                    </button>
                );
            })}
        </div>
    );
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
        self_purchase: Boolean(endorsement.self_purchase),
        financial_mode: endorsement.financial_mode ?? 'reimburse_duluan',
        fee_amount: toCurrencyDigits(endorsement.fee_amount),
        reimburse_amount: toCurrencyDigits(endorsement.reimburse_amount),
        product_cost: toCurrencyDigits(endorsement.product_cost),
        payment_status: endorsement.payment_status ?? 'belum_bayar',
        notes: endorsement.notes ?? '',
    });

    const isEdit = mode === 'edit';
    const reimburseLocked = !form.data.self_purchase || ['reimburse_bersama_fee', 'free_barter', ...NA_MODES].includes(form.data.financial_mode);
    const productLocked = !form.data.self_purchase;
    const totalIncome = amount(form.data.fee_amount) + amount(form.data.reimburse_amount);
    const totalCost = amount(form.data.product_cost);
    const netProfit = totalIncome - totalCost;

    const setData = (key, value) => form.setData(key, value);

    const handleSelfPurchaseChange = (checked) => {
        const next = { self_purchase: checked };

        if (!checked) {
            if (!NA_MODES.includes(form.data.financial_mode)) {
                next.financial_mode = 'na_dikirim_brand';
            }
            next.reimburse_amount = '';
            next.product_cost = '';
        } else if (NA_MODES.includes(form.data.financial_mode)) {
            next.financial_mode = lastManualFinancialMode.current || 'reimburse_duluan';
        }

        form.setData((data) => ({ ...data, ...next }));
    };

    const handleFinancialModeChange = (value) => {
        let nextValue = value;

        if (!form.data.self_purchase && REQUIRES_SELF_PURCHASE.includes(value)) {
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

    const handleSubmit = (event) => {
        event.preventDefault();

        const options = { preserveScroll: true };

        if (isEdit) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/endorsements/${endorsement.id}`, options);

            return;
        }

        form.post('/endorsements', options);
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-5 pb-6">
            <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <h2 className="text-base font-semibold text-foreground">Tahap Pekerjaan</h2>
                <p className="mt-1 text-sm text-muted-foreground">Pilih tahap saat ini. Tahap tidak berubah otomatis — kamu yang kendalikan.</p>
                <div className="mt-4">
                    <StageStrip statusOptions={statusOptions} value={form.data.status} onChange={(value) => setData('status', value)} />
                    <p className="mt-2 text-xs text-muted-foreground">{STATUS_FIELD_HINTS[form.data.status]}</p>
                    <FieldError message={form.errors.status} />
                </div>
            </section>

            <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <h2 className="text-base font-semibold text-foreground">Informasi Campaign</h2>
                <p className="mt-1 text-sm text-muted-foreground">Identitas campaign dan tanggal-tanggal kunci.</p>
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
                    <Field label="Tanggal Deal" htmlFor="deal_date" error={form.errors.deal_date}>
                        <Input id="deal_date" name="deal_date" onChange={(event) => setData('deal_date', event.target.value)} type="date" value={form.data.deal_date} />
                    </Field>
                    <Field label="Order Produk" htmlFor="product_ordered_at" error={form.errors.product_ordered_at}>
                        <Input id="product_ordered_at" name="product_ordered_at" onChange={(event) => setData('product_ordered_at', event.target.value)} type="date" value={form.data.product_ordered_at} />
                    </Field>
                    <Field label="Produk Diterima" htmlFor="product_received_at" error={form.errors.product_received_at}>
                        <Input id="product_received_at" name="product_received_at" onChange={(event) => setData('product_received_at', event.target.value)} type="date" value={form.data.product_received_at} />
                    </Field>
                    <Field label="Deadline Draft" htmlFor="draft_deadline" error={form.errors.draft_deadline}>
                        <Input id="draft_deadline" name="draft_deadline" onChange={(event) => setData('draft_deadline', event.target.value)} type="date" value={form.data.draft_deadline} />
                    </Field>
                </div>
            </section>

            <section className="rounded-3xl border border-border bg-white p-5 shadow-sm">
                <h2 className="text-base font-semibold text-foreground">Keuangan</h2>
                <p className="mt-1 text-sm text-muted-foreground">Fee, reimburse, modal, dan status pembayaran.</p>
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
                        hint={!form.data.self_purchase ? '⚠ Centang "Saya beli produk sendiri" untuk pilih skema Reimburse.' : ''}
                    >
                        <Select id="financial_mode" name="financial_mode" onChange={(event) => handleFinancialModeChange(event.target.value)} value={form.data.financial_mode}>
                            {Object.entries(financialModeOptions).map(([key, label]) => (
                                <option key={key} value={key} disabled={!form.data.self_purchase && REQUIRES_SELF_PURCHASE.includes(key)}>{label}</option>
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
                    <Checkbox label="Saya beli produk sendiri" checked={form.data.self_purchase} name="self_purchase" onChange={handleSelfPurchaseChange} highlight={!form.data.self_purchase} />
                    <CurrencyField
                        label="Modal Produk"
                        name="product_cost"
                        value={form.data.product_cost}
                        onChange={(value) => setData('product_cost', value)}
                        error={form.errors.product_cost}
                        disabled={productLocked}
                    />
                    <Field label="Status Pembayaran *" htmlFor="payment_status" error={form.errors.payment_status}>
                        <Select id="payment_status" name="payment_status" onChange={(event) => setData('payment_status', event.target.value)} value={form.data.payment_status}>
                            {Object.entries(paymentStatusOptions).map(([key, label]) => (
                                <option key={key} value={key}>{label}</option>
                            ))}
                        </Select>
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
