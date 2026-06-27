import { useState } from 'react';
import EndorsementCard from '@/Components/Kanban/EndorsementCard';
import { Plus, Dots, Grip, Pencil, Trash } from '@/Components/Kanban/icons';

/**
 * Satu kolom status (presentasi murni).
 * Semua MUTASI DATA dialirkan lewat callback ke atas (Index.jsx → Inertia router).
 */
export default function KanbanColumn({
  column,
  isOver,
  isColumnDragging,
  rootDnd,
  headerDnd,
  bodyDnd,
  cardDnd,
  onCardClick,
  onAddCard,
  onRenameColumn,
  onDeleteColumn,
}) {
  const [menu, setMenu] = useState(false);
  const [renaming, setRenaming] = useState(false);
  const [name, setName] = useState(column.name);
  const [adding, setAdding] = useState(false);
  const [brand, setBrand] = useState('');

  const startRename = () => { setName(column.name); setRenaming(true); setMenu(false); };
  const saveName = () => { onRenameColumn(column.id, name.trim() || column.name); setRenaming(false); };
  const submitCard = () => { const b = brand.trim(); if (b) { onAddCard(column.id, { brand: b }); setBrand(''); setAdding(false); } };

  return (
    <section
      {...rootDnd}
      className={`flex max-h-full w-72 shrink-0 flex-col rounded-3xl border border-border bg-slate-50/70 transition ${isColumnDragging ? 'opacity-50' : ''} ${isOver ? 'ring-2 ring-indigo-300' : ''}`}
    >
      {/* Header */}
      <div className="flex items-center gap-2 px-4 pb-2 pt-4">
        <button {...headerDnd} title="Tarik untuk ubah urutan kolom" className="cursor-grab text-slate-300 transition hover:text-slate-500 active:cursor-grabbing">
          <Grip size={16} />
        </button>
        <span className="h-2.5 w-2.5 shrink-0 rounded-full" style={{ background: column.accent || '#94a3b8' }} />

        {renaming ? (
          <input
            autoFocus
            value={name}
            onChange={(e) => setName(e.target.value)}
            onBlur={saveName}
            onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); e.target.blur(); } if (e.key === 'Escape') setRenaming(false); }}
            className="min-w-0 flex-1 rounded-lg border border-indigo-500 px-2 py-1 text-sm font-bold outline-none"
          />
        ) : (
          <h3 onDoubleClick={startRename} title="Klik dua kali untuk ganti nama" className="min-w-0 flex-1 truncate text-sm font-bold tracking-tight text-slate-800">
            {column.name}
          </h3>
        )}

        <span className="rounded-lg bg-white px-2 py-0.5 text-xs font-bold text-slate-400 ring-1 ring-inset ring-border">
          {column.cards.length}
        </span>

        <div className="relative">
          <button onClick={() => setMenu((m) => !m)} className="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white hover:text-slate-600">
            <Dots size={16} />
          </button>
          {menu ? (
            <>
              <div className="fixed inset-0 z-10" onClick={() => setMenu(false)} />
              <div className="absolute right-0 top-8 z-20 w-44 rounded-xl border border-border bg-white p-1.5 shadow-lg">
                <button onClick={startRename} className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-[13px] font-medium text-slate-700 transition hover:bg-slate-50">
                  <Pencil size={14} /> Ganti nama
                </button>
                <button onClick={() => { setAdding(true); setMenu(false); }} className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-[13px] font-medium text-slate-700 transition hover:bg-slate-50">
                  <Plus size={14} /> Tambah kartu
                </button>
                <div className="my-1 h-px bg-slate-100" />
                <button onClick={() => { setMenu(false); onDeleteColumn(column.id); }} className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-[13px] font-medium text-rose-600 transition hover:bg-rose-50">
                  <Trash size={14} /> Hapus kolom
                </button>
              </div>
            </>
          ) : null}
        </div>
      </div>

      {/* Body / daftar kartu */}
      <div {...bodyDnd} className="flex min-h-[8px] flex-1 flex-col gap-2.5 overflow-y-auto px-3 pb-3 pt-1">
        {column.cards.map((card) => (
          <EndorsementCard key={card.id} card={card} onClick={() => onCardClick(card)} {...cardDnd(card)} />
        ))}

        {adding ? (
          <div className="rounded-2xl border border-border bg-white p-2.5 shadow-sm">
            <input
              autoFocus
              value={brand}
              onChange={(e) => setBrand(e.target.value)}
              onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); submitCard(); } if (e.key === 'Escape') { setAdding(false); setBrand(''); } }}
              placeholder="Nama brand…"
              className="w-full rounded-lg px-2 py-1.5 text-sm font-medium outline-none"
            />
            <div className="mt-2 flex gap-2">
              <button onClick={submitCard} className="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700">Tambah</button>
              <button onClick={() => { setAdding(false); setBrand(''); }} className="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100">Batal</button>
            </div>
          </div>
        ) : (
          <button onClick={() => setAdding(true)} className="flex items-center gap-2 rounded-xl px-2.5 py-2 text-[13px] font-semibold text-slate-400 transition hover:bg-white hover:text-indigo-600">
            <Plus size={15} /> Tambah kartu
          </button>
        )}
      </div>
    </section>
  );
}
