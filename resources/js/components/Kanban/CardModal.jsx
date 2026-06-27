import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { PLATFORMS, PAYMENT_STATUS } from '@/lib/kanban';
import { Close, Trash, ExternalLink } from '@/Components/Kanban/icons';

/**
 * Modal detail endorsement — controlled.
 * Edit cepat (brand, campaign, platform, payment_status, posting_date, notes).
 * Profit & nilai ditampilkan read-only — edit lengkap lewat link ke halaman Edit.
 *
 * @param {object|null} props.card      Kartu aktif (null = modal tertutup)
 * @param {Function}    props.onClose   () => void
 * @param {Function}    props.onSave    (patch) => void  — patch = field yang berubah
 * @param {Function}    props.onDelete  () => void
 */
export default function CardModal({ card, onClose, onSave, onDelete }) {
  const [patch, setPatch] = useState({});
  const [confirmDelete, setConfirmDelete] = useState(false);

  if (!card) return null;

  // Nilai tampilan = patch (pending) atau nilai asli dari card
  const val = (key) => (key in patch ? patch[key] : card[key]);
  const set = (update) => setPatch((p) => ({ ...p, ...update }));

  const handleSave = () => {
    if (Object.keys(patch).length > 0) onSave(patch);
    setPatch({});
    onClose();
  };

  const handleClose = () => {
    setPatch({});
    setConfirmDelete(false);
    onClose();
  };

  const handleDelete = () => {
    if (!confirmDelete) { setConfirmDelete(true); return; }
    onDelete();
  };

  return (
    <div onClick={handleClose} className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 backdrop-blur-sm sm:p-10">
      <div onClick={(e) => e.stopPropagation()} className="w-full max-w-xl rounded-3xl border border-border bg-white shadow-2xl">
        {/* Header */}
        <div className="flex items-center justify-between border-b border-border px-6 py-4">
          <h2 className="text-base font-bold tracking-tight text-slate-900">Detail Endorsement</h2>
          <div className="flex items-center gap-2">
            <Link href={card.editUrl} className="rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
              Edit lengkap <ExternalLink size={12} className="inline-block" />
            </Link>
            <button onClick={handleClose} className="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
              <Close size={18} />
            </button>
          </div>
        </div>

        {/* Body */}
        <div className="max-h-[65vh] space-y-5 overflow-y-auto px-6 py-5">
          <ModalField label="Brand">
            <input value={val('brand')} onChange={(e) => set({ brand_name: e.target.value, brand: e.target.value })} placeholder="Nama brand" className="w-full rounded-xl border border-border bg-white px-3 py-2.5 text-base font-bold tracking-tight outline-none transition focus:border-indigo-500" />
          </ModalField>

          <ModalField label="Campaign">
            <input value={val('campaign') || ''} onChange={(e) => set({ campaign_name: e.target.value, campaign: e.target.value })} placeholder="Nama campaign" className="w-full rounded-xl border border-border bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-500" />
          </ModalField>

          <ModalField label="Platform">
            <div className="flex flex-wrap gap-1.5">
              {Object.entries(PLATFORMS).map(([key, p]) => (
                <button
                  key={key}
                  onClick={() => set({ platform: key })}
                  className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition ${val('platform') === key ? 'bg-indigo-600 text-white' : 'border border-border bg-white text-slate-600 hover:bg-slate-50'}`}
                >
                  {p.label}
                </button>
              ))}
            </div>
          </ModalField>

          <ModalField label="Status Pembayaran">
            <div className="flex flex-wrap gap-1.5">
              {Object.entries(PAYMENT_STATUS).map(([key, p]) => (
                <button
                  key={key}
                  onClick={() => set({ payment_status: key, paymentStatus: key })}
                  className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition ${val('paymentStatus') === key ? 'ring-2 ring-inset ' + p.badge : 'border border-border bg-white text-slate-600 hover:bg-slate-50'}`}
                >
                  <span className={`h-1.5 w-1.5 rounded-full ${p.dot}`} />
                  {p.label}
                </button>
              ))}
            </div>
          </ModalField>

          <ModalField label="Rencana Posting">
            <input type="date" value={val('deadline') || ''} onChange={(e) => set({ posting_date: e.target.value, deadline: e.target.value })} className="rounded-xl border border-border bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-500" />
          </ModalField>

          <ModalField label="Catatan">
            <textarea rows="3" value={val('notes') || ''} onChange={(e) => set({ notes: e.target.value })} placeholder="Brief, syarat, atau catatan lain…" className="w-full resize-none rounded-xl border border-border bg-white px-3 py-2.5 text-sm leading-relaxed outline-none transition focus:border-indigo-500" />
          </ModalField>

          {/* Finansial — read only, edit lewat halaman full */}
          <div className="grid grid-cols-2 gap-4">
            <ModalField label="Nilai Kontrak (read-only)">
              <div className="rounded-xl border border-border bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-500">
                Rp {(Number(card.value) || 0).toLocaleString('id-ID')}
              </div>
            </ModalField>
            <ModalField label="Laba Bersih (read-only)">
              <div className={`rounded-xl border border-border bg-slate-50 px-3 py-2.5 text-sm font-bold ${card.profit >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                Rp {(Number(card.profit) || 0).toLocaleString('id-ID')}
              </div>
            </ModalField>
          </div>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between border-t border-border bg-slate-50 px-6 py-4">
          {confirmDelete ? (
            <div className="flex items-center gap-2">
              <span className="text-sm text-slate-600">Yakin hapus?</span>
              <button onClick={handleDelete} className="rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-700">Ya, hapus</button>
              <button onClick={() => setConfirmDelete(false)} className="rounded-xl px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-200">Batal</button>
            </div>
          ) : (
            <button onClick={handleDelete} className="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
              <Trash size={15} /> Hapus
            </button>
          )}
          <button onClick={handleSave} className="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Selesai</button>
        </div>
      </div>
    </div>
  );
}

function ModalField({ label, children }) {
  return (
    <div>
      <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</label>
      {children}
    </div>
  );
}
