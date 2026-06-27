import { formatRupiah, formatDate, PLATFORMS, PAYMENT_STATUS } from '@/lib/kanban';

/**
 * Kartu endorsement — komponen presentasi murni (tanpa state data).
 *
 * @param {object}   props.card        Data endorsement dari backend (brand, campaign, platform, paymentStatus, profit, deadline)
 * @param {boolean}  props.isDragging  Sedang ditarik (untuk efek redup)
 * @param {Function} props.onClick     Klik kartu -> buka detail modal
 */
export default function EndorsementCard({
  card,
  isDragging = false,
  onClick,
  draggable,
  onDragStart,
  onDragEnd,
  onDragOver,
  onDrop,
}) {
  const platform = PLATFORMS[card.platform] || { label: card.platform, badge: 'bg-slate-100 text-slate-700 ring-slate-200' };
  const pay = PAYMENT_STATUS[card.paymentStatus] || PAYMENT_STATUS.belum_bayar;
  const initial = (card.brand || '?').trim().charAt(0).toUpperCase();

  return (
    <article
      draggable={draggable}
      onDragStart={onDragStart}
      onDragEnd={onDragEnd}
      onDragOver={onDragOver}
      onDrop={onDrop}
      onClick={onClick}
      className={`group cursor-pointer select-none rounded-2xl border border-border bg-white p-3.5 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md ${isDragging ? 'opacity-40' : ''}`}
    >
      <div className="flex items-center justify-between gap-2">
        <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset ${platform.badge}`}>
          {platform.label}
        </span>
        <span className={`inline-flex items-center gap-1.5 rounded-lg px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset ${pay.badge}`}>
          <span className={`h-1.5 w-1.5 rounded-full ${pay.dot}`} />
          {pay.label}
        </span>
      </div>

      <div className="mt-3 flex items-center gap-2.5">
        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">
          {initial}
        </div>
        <div className="min-w-0">
          <h4 className="truncate text-sm font-bold tracking-tight text-slate-900">{card.brand || 'Tanpa Brand'}</h4>
          {card.campaign ? <p className="truncate text-xs text-slate-500">{card.campaign}</p> : null}
        </div>
      </div>

      <div className="mt-3 flex items-end justify-between border-t border-slate-100 pt-3">
        <div>
          <div className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Laba</div>
          <div className={`text-sm font-bold ${card.profit >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>{formatRupiah(card.profit)}</div>
        </div>
        {card.deadline ? (
          <span className="inline-flex items-center gap-1 rounded-lg bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500">
            {formatDate(card.deadline)}
          </span>
        ) : null}
      </div>
    </article>
  );
}
