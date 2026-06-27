import { useState, useEffect, useMemo } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import KanbanBoard from '@/Components/Kanban/KanbanBoard';
import EditModal from '@/Components/Kanban/EditModal';
import { formatRupiah } from '@/lib/kanban';

function buildQuery(data) {
  return Object.fromEntries(
    Object.entries(data).filter(([, v]) => v !== '' && v !== null && v !== undefined),
  );
}

export default function EndorsementsIndex({
  columns: initialColumns,
  statusOptions,
  platformOptions,
  contentTypeOptions,
  financialModeOptions,
  paymentStatusOptions,
  filters,
}) {
  const [columns, setColumns] = useState(initialColumns);
  const [activeCard, setActiveCard] = useState(null);

  useEffect(() => setColumns(initialColumns), [initialColumns]);

  const stats = useMemo(() => {
    let count = 0, profit = 0, value = 0, paid = 0;
    columns.forEach((c) => c.cards.forEach((card) => {
      count++;
      profit += Number(card.profit) || 0;
      value += Number(card.value) || 0;
      if (card.paymentStatus === 'lunas') paid++;
    }));
    return { count, profit, value, paid };
  }, [columns]);

  // ── Callback kolom ───────────────────────────────────────────────────────
  const onAddColumn = (name) => {
    router.post('/kanban-columns', { name }, { preserveScroll: true });
  };

  const onRenameColumn = (slug, name) => {
    setColumns((cs) => cs.map((c) => c.id === slug ? { ...c, name } : c));
    router.patch(`/kanban-columns/${slug}/rename`, { name }, { preserveScroll: true, preserveState: true });
  };

  const onReorderColumns = (orderedSlugs) => {
    setColumns((cs) => {
      const byId = Object.fromEntries(cs.map((c) => [c.id, c]));
      return orderedSlugs.map((id) => byId[id]).filter(Boolean);
    });
    router.post('/kanban-columns/reorder', { slugs: orderedSlugs }, { preserveScroll: true, preserveState: true });
  };

  const onDeleteColumn = (slug) => {
    router.delete(`/kanban-columns/${slug}`, { preserveScroll: true });
  };

  // ── Callback kartu ───────────────────────────────────────────────────────
  const onMoveCard = (cardId, toColumnId) => {
    setColumns((cs) => {
      let moving = null;
      const without = cs.map((c) => {
        const found = c.cards.find((x) => x.id === cardId);
        if (!found) return c;
        moving = { ...found, status: toColumnId };
        return { ...c, cards: c.cards.filter((x) => x.id !== cardId) };
      });
      if (!moving) return cs;
      return without.map((c) => {
        if (c.id !== toColumnId) return c;
        return { ...c, cards: [...c.cards, moving] };
      });
    });

    router.post(`/endorsements/${cardId}/status`, { status: toColumnId }, {
      preserveScroll: true,
      preserveState: true,
      onError: () => setColumns(initialColumns),
    });
  };

  const onAddCard = (columnId, partial) => {
    router.post('/endorsements-quick', {
      brand_name: partial?.brand || 'Brand Baru',
      status: columnId,
    }, { preserveScroll: true });
  };


  return (
    <AppLayout>
      {/*
        Wrapper full-height: di desktop AppLayout hanya punya py-4 (2rem) di shell,
        mobile ada sticky nav ~56px ≈ 3.5rem.
      */}
      <div className="flex h-[calc(100dvh-3.5rem)] min-w-0 flex-col overflow-hidden rounded-3xl border border-border bg-white shadow-sm lg:h-[calc(100dvh-2rem)]">

        {/* ── Header papan ─────────────────────────────────────────────── */}
        <header className="shrink-0 border-b border-border bg-white px-4 py-3 lg:px-6 lg:py-4">
          <div className="flex items-center justify-between gap-3">
            <h1 className="text-base font-extrabold tracking-tight text-slate-900 lg:text-xl">Papan Endorsement</h1>
            {/* Stats — scroll horizontal di mobile agar tidak wrap */}
            <div className="-mr-4 overflow-x-auto lg:-mr-6">
              <div className="flex items-center gap-4 pr-4 lg:gap-5 lg:pr-6" style={{ minWidth: 'max-content' }}>
                <Stat label="Endorse" value={stats.count} />
                <Divider />
                <Stat label="Lunas" value={stats.paid} accent="text-emerald-600" />
                <Divider />
                <Stat label="Laba" value={formatRupiah(stats.profit)} accent="text-emerald-600" />
                <Divider />
                <Stat label="Kontrak" value={formatRupiah(stats.value)} />
              </div>
            </div>
          </div>

          {/* Filter bar — scroll horizontal di mobile */}
          <FilterBar filters={filters} paymentStatusOptions={paymentStatusOptions} />
        </header>

        {/* ── Papan kanban ─────────────────────────────────────────────── */}
        <div className="min-h-0 min-w-0 flex-1 overflow-hidden bg-slate-100 pt-5">
          <KanbanBoard
            columns={columns}
            onAddColumn={onAddColumn}
            onRenameColumn={onRenameColumn}
            onReorderColumns={onReorderColumns}
            onDeleteColumn={onDeleteColumn}
            onMoveCard={onMoveCard}
            onAddCard={onAddCard}
            onCardClick={(card) => setActiveCard(card)}
          />
        </div>
      </div>

      <EditModal
        card={activeCard}
        options={{ statusOptions, platformOptions, contentTypeOptions, financialModeOptions, paymentStatusOptions }}
        onClose={() => setActiveCard(null)}
      />
    </AppLayout>
  );
}

// ── Sub-komponen ─────────────────────────────────────────────────────────────

function Stat({ label, value, accent = 'text-slate-900' }) {
  return (
    <div className="text-right">
      <div className={`text-lg font-extrabold leading-none ${accent}`}>{value}</div>
      <div className="mt-1 text-[11px] font-semibold text-slate-400">{label}</div>
    </div>
  );
}

function Divider() {
  return <div className="h-8 w-px bg-border" />;
}

function FilterBar({ filters, paymentStatusOptions }) {
  const [form, setForm] = useState({
    q: filters.q ?? '',
    payment_status: filters.payment_status ?? '',
    insight: filters.insight ?? '',
  });

  const apply = (next) =>
    router.get('/endorsements', buildQuery(next), { preserveScroll: true, preserveState: true, replace: true });

  const submit = (e) => { e.preventDefault(); apply(form); };
  const reset = () => {
    const f = { q: '', payment_status: '', insight: '' };
    setForm(f);
    router.get('/endorsements', {}, { preserveScroll: true, replace: true });
  };

  return (
    <div className="-mx-4 mt-2 overflow-x-auto lg:-mx-6">
      <form onSubmit={submit} className="flex items-center gap-2 px-4 pb-1 lg:px-6" style={{ minWidth: 'max-content' }}>
        <input
          className="rounded-xl border border-border bg-slate-50 px-3 py-1.5 text-sm outline-none transition focus:border-indigo-500 focus:bg-white"
          onChange={(e) => setForm((f) => ({ ...f, q: e.target.value }))}
          placeholder="Cari brand…"
          value={form.q}
        />
        <select
          className="rounded-xl border border-border bg-slate-50 px-3 py-1.5 text-sm outline-none transition focus:border-indigo-500 focus:bg-white"
          onChange={(e) => { const f = { ...form, payment_status: e.target.value }; setForm(f); apply(f); }}
          value={form.payment_status}
        >
          <option value="">Semua bayar</option>
          {Object.entries(paymentStatusOptions).map(([key, label]) => (
            <option key={key} value={key}>{label}</option>
          ))}
        </select>
        <select
          className="rounded-xl border border-border bg-slate-50 px-3 py-1.5 text-sm outline-none transition focus:border-indigo-500 focus:bg-white"
          onChange={(e) => { const f = { ...form, insight: e.target.value }; setForm(f); apply(f); }}
          value={form.insight}
        >
          <option value="">Semua laporan</option>
          <option value="waiting">Menunggu</option>
          <option value="overdue">Terlambat</option>
          <option value="sent">Terkirim</option>
        </select>
        <button
          className="rounded-xl border border-border bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-500 transition hover:bg-white hover:text-slate-700"
          type="submit"
        >
          Cari
        </button>
        {(form.q || form.payment_status || form.insight) && (
          <button
            className="rounded-xl px-2 py-1.5 text-sm font-semibold text-slate-400 transition hover:text-slate-600"
            onClick={reset}
            type="button"
          >
            Reset
          </button>
        )}
        <a
          href={`/endorsements-export?${new URLSearchParams(buildQuery(form)).toString()}`}
          className="rounded-xl border border-border bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-500 transition hover:bg-white hover:text-slate-700"
        >
          CSV
        </a>
      </form>
    </div>
  );
}
