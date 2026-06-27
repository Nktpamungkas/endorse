import { useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { formatCurrency, formatCurrencyInput, toCurrencyDigits } from '@/lib/formatters';
import { Close, Trash } from '@/Components/Kanban/icons';

const NA_MODES = ['na_dikirim_brand', 'na_tanpa_produk'];
const REQUIRES_SELF_PURCHASE = ['reimburse_duluan', 'reimburse_bersama_fee'];

function amount(v) { return Number(toCurrencyDigits(v) || 0); }

// ── Atom UI ──────────────────────────────────────────────────────────────────

function Label({ children }) {
  return <p className="mb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">{children}</p>;
}

function Err({ msg }) {
  return msg ? <p className="mt-1 text-xs text-rose-600">{msg}</p> : null;
}

function MInput({ error, className = '', ...props }) {
  return (
    <>
      <input className={`w-full rounded-xl border bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-500 ${error ? 'border-rose-400' : 'border-border'} ${className}`} {...props} />
      <Err msg={error} />
    </>
  );
}

function MSelect({ error, children, ...props }) {
  return (
    <>
      <select className={`w-full rounded-xl border bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-500 ${error ? 'border-rose-400' : 'border-border'}`} {...props}>
        {children}
      </select>
      <Err msg={error} />
    </>
  );
}

function MTextarea({ error, ...props }) {
  return (
    <>
      <textarea className={`w-full resize-none rounded-xl border bg-white px-3 py-2 text-sm leading-relaxed outline-none transition focus:border-indigo-500 ${error ? 'border-rose-400' : 'border-border'}`} {...props} />
      <Err msg={error} />
    </>
  );
}

function CurrencyInput({ value, onChange, disabled, error }) {
  return (
    <>
      <div className={`flex overflow-hidden rounded-xl border bg-white transition focus-within:border-indigo-500 ${disabled ? 'opacity-50' : ''} ${error ? 'border-rose-400' : 'border-border'}`}>
        <span className="border-r border-border bg-slate-50 px-3 py-2 text-sm text-slate-400">Rp</span>
        <input
          className="w-full bg-transparent px-3 py-2 text-sm outline-none"
          disabled={disabled}
          inputMode="numeric"
          onChange={(e) => onChange(toCurrencyDigits(e.target.value))}
          placeholder="0"
          value={formatCurrencyInput(value)}
        />
      </div>
      <Err msg={error} />
    </>
  );
}

function SectionHead({ title }) {
  return <h3 className="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">{title}</h3>;
}

// ── Modal utama ───────────────────────────────────────────────────────────────

/**
 * Modal edit lengkap endorsement.
 * card = data dari toCard() yang sudah diperluas (termasuk field form).
 * options = { statusOptions, platformOptions, contentTypeOptions, financialModeOptions, paymentStatusOptions }
 */
export default function EditModal({ card, options, onClose }) {
  if (!card) return null;

  const { statusOptions, platformOptions, contentTypeOptions, financialModeOptions, paymentStatusOptions } = options;

  const lastManualMode = useRef(
    card.financial_mode && !NA_MODES.includes(card.financial_mode)
      ? card.financial_mode
      : 'reimburse_duluan',
  );

  const form = useForm({
    brand_name: card.brand ?? '',
    campaign_name: card.campaign ?? '',
    platform: card.platform ?? 'instagram',
    content_type: card.content_type ?? 'video',
    status: card.status ?? '',
    deal_date: card.deal_date ?? '',
    product_ordered_at: card.product_ordered_at ?? '',
    product_received_at: card.product_received_at ?? '',
    draft_deadline: card.draft_deadline ?? '',
    posting_date: card.posting_date ?? '',
    self_purchase: Boolean(card.self_purchase),
    financial_mode: card.financial_mode ?? 'reimburse_duluan',
    fee_amount: toCurrencyDigits(card.fee_amount ?? '0'),
    reimburse_amount: toCurrencyDigits(card.reimburse_amount ?? '0'),
    product_cost: toCurrencyDigits(card.product_cost ?? '0'),
    payment_status: card.paymentStatus ?? 'belum_bayar',
    notes: card.notes ?? '',
  });

  const [confirmDelete, setConfirmDelete] = useState(false);

  // Derived
  const reimburseLocked = !form.data.self_purchase
    || ['reimburse_bersama_fee', 'free_barter', ...NA_MODES].includes(form.data.financial_mode);
  const productLocked = !form.data.self_purchase;
  const totalIncome = amount(form.data.fee_amount) + amount(form.data.reimburse_amount);
  const totalCost = amount(form.data.product_cost);
  const netProfit = totalIncome - totalCost;

  const set = (key, val) => form.setData(key, val);

  const handleSelfPurchase = (checked) => {
    const next = { self_purchase: checked };
    if (!checked) {
      if (!NA_MODES.includes(form.data.financial_mode)) next.financial_mode = 'na_dikirim_brand';
      next.reimburse_amount = '';
      next.product_cost = '';
    } else if (NA_MODES.includes(form.data.financial_mode)) {
      next.financial_mode = lastManualMode.current || 'reimburse_duluan';
    }
    form.setData((d) => ({ ...d, ...next }));
  };

  const handleFinancialMode = (val) => {
    let next = val;
    if (!form.data.self_purchase && REQUIRES_SELF_PURCHASE.includes(val)) next = 'na_dikirim_brand';
    if (!NA_MODES.includes(next)) lastManualMode.current = next;
    form.setData((d) => ({
      ...d,
      financial_mode: next,
      reimburse_amount: next === 'reimburse_duluan' ? d.reimburse_amount : '',
      fee_amount: next === 'free_barter' ? '' : d.fee_amount,
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    form.transform((d) => ({ ...d, _method: 'put' }));
    form.post(`/endorsements/${card.id}`, {
      preserveScroll: true,
      onSuccess: onClose,
    });
  };

  const handleDelete = () => {
    if (!confirmDelete) { setConfirmDelete(true); return; }
    router.delete(`/endorsements/${card.id}`, {
      data: { delete_reason: 'Dihapus melalui papan Kanban' },
      preserveScroll: true,
      onSuccess: onClose,
    });
  };

  return (
    <div onClick={onClose} className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 backdrop-blur-sm sm:p-8">
      <form
        onClick={(e) => e.stopPropagation()}
        onSubmit={handleSubmit}
        className="my-4 w-full max-w-2xl rounded-3xl border border-border bg-white shadow-2xl"
      >
        {/* Header */}
        <div className="flex items-center justify-between border-b border-border px-6 py-4">
          <div>
            <h2 className="text-base font-bold tracking-tight text-slate-900">Edit Endorsement</h2>
            <p className="text-xs text-slate-400">{card.brand}</p>
          </div>
          <button type="button" onClick={onClose} className="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
            <Close size={18} />
          </button>
        </div>

        {/* Body */}
        <div className="max-h-[70vh] space-y-6 overflow-y-auto px-6 py-5">

          {/* Tahap */}
          <div>
            <SectionHead title="Tahap Pekerjaan" />
            <div className="flex flex-wrap gap-1.5">
              {Object.entries(statusOptions).map(([key, label]) => (
                <button
                  key={key}
                  type="button"
                  onClick={() => set('status', key)}
                  className={`rounded-full border px-3 py-1.5 text-xs font-semibold transition ${
                    form.data.status === key
                      ? 'border-indigo-500 bg-indigo-600 text-white'
                      : 'border-border bg-white text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  {label}
                </button>
              ))}
            </div>
            <Err msg={form.errors.status} />
          </div>

          {/* Informasi Campaign */}
          <div>
            <SectionHead title="Informasi Campaign" />
            <div className="grid grid-cols-2 gap-3">
              <div className="col-span-2 sm:col-span-1">
                <Label>Nama Brand *</Label>
                <MInput value={form.data.brand_name} onChange={(e) => set('brand_name', e.target.value)} placeholder="Contoh: Wardah" error={form.errors.brand_name} />
              </div>
              <div className="col-span-2 sm:col-span-1">
                <Label>Nama Campaign</Label>
                <MInput value={form.data.campaign_name} onChange={(e) => set('campaign_name', e.target.value)} placeholder="Contoh: Launching serum" error={form.errors.campaign_name} />
              </div>
              <div>
                <Label>Platform *</Label>
                <MSelect value={form.data.platform} onChange={(e) => set('platform', e.target.value)} error={form.errors.platform}>
                  {Object.entries(platformOptions).map(([k, l]) => <option key={k} value={k}>{l}</option>)}
                </MSelect>
              </div>
              <div>
                <Label>Jenis Konten *</Label>
                <MSelect value={form.data.content_type} onChange={(e) => set('content_type', e.target.value)} error={form.errors.content_type}>
                  {Object.entries(contentTypeOptions).map(([k, l]) => <option key={k} value={k}>{l}</option>)}
                </MSelect>
              </div>
              <div>
                <Label>Tanggal Deal</Label>
                <MInput type="date" value={form.data.deal_date} onChange={(e) => set('deal_date', e.target.value)} error={form.errors.deal_date} />
              </div>
              <div>
                <Label>Deadline Draft</Label>
                <MInput type="date" value={form.data.draft_deadline} onChange={(e) => set('draft_deadline', e.target.value)} error={form.errors.draft_deadline} />
              </div>
              <div>
                <Label>Order Produk</Label>
                <MInput type="date" value={form.data.product_ordered_at} onChange={(e) => set('product_ordered_at', e.target.value)} error={form.errors.product_ordered_at} />
              </div>
              <div>
                <Label>Produk Diterima</Label>
                <MInput type="date" value={form.data.product_received_at} onChange={(e) => set('product_received_at', e.target.value)} error={form.errors.product_received_at} />
              </div>
              <div>
                <Label>Rencana Posting</Label>
                <MInput type="date" value={form.data.posting_date} onChange={(e) => set('posting_date', e.target.value)} error={form.errors.posting_date} />
              </div>
            </div>
          </div>

          {/* Keuangan */}
          <div>
            <SectionHead title="Keuangan" />

            {/* Ringkasan */}
            <div className="mb-4 grid grid-cols-3 gap-3 rounded-2xl bg-slate-50 p-3">
              <div>
                <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Pendapatan</p>
                <p className="mt-1 text-sm font-bold text-slate-800">{formatCurrency(totalIncome)}</p>
              </div>
              <div>
                <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Modal</p>
                <p className="mt-1 text-sm font-bold text-slate-800">{formatCurrency(totalCost)}</p>
              </div>
              <div>
                <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Estimasi Laba</p>
                <p className={`mt-1 text-sm font-bold ${netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>{formatCurrency(netProfit)}</p>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="col-span-2">
                <Label>
                  Skema Finansial *
                  {!form.data.self_purchase && <span className="ml-1 font-normal normal-case text-amber-600">— centang "Beli sendiri" untuk pilih skema Reimburse</span>}
                </Label>
                <MSelect value={form.data.financial_mode} onChange={(e) => handleFinancialMode(e.target.value)} error={form.errors.financial_mode}>
                  {Object.entries(financialModeOptions).map(([k, l]) => (
                    <option key={k} value={k} disabled={!form.data.self_purchase && REQUIRES_SELF_PURCHASE.includes(k)}>{l}</option>
                  ))}
                </MSelect>
              </div>
              <div>
                <Label>Fee</Label>
                <CurrencyInput value={form.data.fee_amount} onChange={(v) => set('fee_amount', v)} error={form.errors.fee_amount} disabled={form.data.financial_mode === 'free_barter'} />
              </div>
              <div>
                <Label>Nominal Reimburse</Label>
                <CurrencyInput value={form.data.reimburse_amount} onChange={(v) => set('reimburse_amount', v)} error={form.errors.reimburse_amount} disabled={reimburseLocked} />
              </div>
              <div>
                <label className={`flex cursor-pointer items-start gap-3 rounded-xl border px-3 py-3 text-sm transition ${!form.data.self_purchase ? 'border-amber-300 bg-amber-50' : 'border-border bg-white'}`}>
                  <input
                    type="checkbox"
                    checked={form.data.self_purchase}
                    onChange={(e) => handleSelfPurchase(e.target.checked)}
                    className="mt-0.5 h-4 w-4 rounded border-border text-indigo-600"
                  />
                  <span>Saya beli produk sendiri</span>
                </label>
              </div>
              <div>
                <Label>Modal Produk</Label>
                <CurrencyInput value={form.data.product_cost} onChange={(v) => set('product_cost', v)} error={form.errors.product_cost} disabled={productLocked} />
              </div>
              <div className="col-span-2">
                <Label>Status Pembayaran *</Label>
                <div className="flex flex-wrap gap-1.5">
                  {Object.entries(paymentStatusOptions).map(([k, l]) => (
                    <button
                      key={k}
                      type="button"
                      onClick={() => set('payment_status', k)}
                      className={`rounded-xl border px-3 py-2 text-xs font-semibold transition ${form.data.payment_status === k ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-border bg-white text-slate-600 hover:bg-slate-50'}`}
                    >
                      {l}
                    </button>
                  ))}
                </div>
                <Err msg={form.errors.payment_status} />
              </div>
              <div className="col-span-2">
                <Label>Catatan</Label>
                <MTextarea rows={3} value={form.data.notes} onChange={(e) => set('notes', e.target.value)} placeholder="Brief, syarat, atau catatan lain…" error={form.errors.notes} />
              </div>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between border-t border-border bg-slate-50 px-6 py-4">
          {confirmDelete ? (
            <div className="flex items-center gap-2">
              <span className="text-sm text-slate-600">Yakin hapus?</span>
              <button type="button" onClick={handleDelete} className="rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-700">Ya, hapus</button>
              <button type="button" onClick={() => setConfirmDelete(false)} className="rounded-xl px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-200">Batal</button>
            </div>
          ) : (
            <button type="button" onClick={handleDelete} className="flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
              <Trash size={15} /> Hapus
            </button>
          )}
          <div className="flex items-center gap-2">
            <button type="button" onClick={onClose} className="rounded-xl border border-border px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
              Batal
            </button>
            <button
              type="submit"
              disabled={form.processing}
              className="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {form.processing ? 'Menyimpan…' : 'Simpan'}
            </button>
          </div>
        </div>
      </form>
    </div>
  );
}
