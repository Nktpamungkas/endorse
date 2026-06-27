import { useState } from 'react';
import KanbanColumn from '@/Components/Kanban/KanbanColumn';
import { Plus } from '@/Components/Kanban/icons';

/**
 * Papan Kanban — komponen presentasi murni.
 * Semua perubahan DATA dialirkan lewat callback agar mudah disambung backend (Inertia).
 */
export default function KanbanBoard({
  columns,
  onAddColumn,
  onRenameColumn,
  onReorderColumns,
  onDeleteColumn,
  onMoveCard,
  onAddCard,
  onCardClick,
}) {
  // drag = { kind: 'card', cardId, fromColumnId } | { kind: 'column', columnId } | null
  const [drag, setDrag] = useState(null);
  const [overCol, setOverCol] = useState(null);
  const [adding, setAdding] = useState(false);
  const [draft, setDraft] = useState('');

  const endDrag = () => { setDrag(null); setOverCol(null); };

  // --- DnD kartu ---
  const cardDnd = (card, columnId) => ({
    draggable: true,
    isDragging: drag?.kind === 'card' && drag.cardId === card.id,
    onDragStart: (e) => {
      e.stopPropagation();
      setDrag({ kind: 'card', cardId: card.id, fromColumnId: columnId });
      try { e.dataTransfer.effectAllowed = 'move'; } catch (_) {}
    },
    onDragEnd: endDrag,
    onDragOver: (e) => {
      if (drag?.kind === 'card') { e.preventDefault(); e.stopPropagation(); if (overCol !== columnId) setOverCol(columnId); }
    },
    onDrop: (e) => {
      if (drag?.kind === 'card') {
        e.preventDefault(); e.stopPropagation();
        const col = columns.find((c) => c.id === columnId);
        const index = col ? col.cards.findIndex((x) => x.id === card.id) : null;
        onMoveCard(drag.cardId, columnId, index);
        endDrag();
      }
    },
  });

  // --- Drop kartu di area kosong kolom (taruh paling bawah) ---
  const bodyDnd = (columnId) => ({
    onDragOver: (e) => { if (drag?.kind === 'card') { e.preventDefault(); if (overCol !== columnId) setOverCol(columnId); } },
    onDrop: (e) => { if (drag?.kind === 'card') { e.preventDefault(); onMoveCard(drag.cardId, columnId, null); endDrag(); } },
  });

  // --- Drag grip kolom ---
  const headerDnd = (columnId) => ({
    draggable: true,
    onDragStart: (e) => { setDrag({ kind: 'column', columnId }); try { e.dataTransfer.effectAllowed = 'move'; } catch (_) {} },
    onDragEnd: endDrag,
  });

  // --- Kolom sebagai target reorder ---
  const rootDnd = (columnId) => ({
    onDragOver: (e) => { if (drag?.kind === 'column' && drag.columnId !== columnId) { e.preventDefault(); if (overCol !== columnId) setOverCol(columnId); } },
    onDrop: (e) => {
      if (drag?.kind === 'column') {
        e.preventDefault();
        const ids = columns.map((c) => c.id);
        const from = ids.indexOf(drag.columnId);
        const to = ids.indexOf(columnId);
        if (from > -1 && to > -1) { ids.splice(from, 1); ids.splice(to, 0, drag.columnId); onReorderColumns(ids); }
        endDrag();
      }
    },
  });

  const submitColumn = () => { const n = draft.trim(); if (n) onAddColumn(n); setDraft(''); setAdding(false); };

  return (
    <div className="flex h-full items-start gap-4 overflow-x-auto px-6 pb-6">
      {columns.map((column) => (
        <KanbanColumn
          key={column.id}
          column={column}
          isOver={overCol === column.id}
          isColumnDragging={drag?.kind === 'column' && drag.columnId === column.id}
          rootDnd={rootDnd(column.id)}
          headerDnd={headerDnd(column.id)}
          bodyDnd={bodyDnd(column.id)}
          cardDnd={(card) => cardDnd(card, column.id)}
          onCardClick={onCardClick}
          onAddCard={onAddCard}
          onRenameColumn={onRenameColumn}
          onDeleteColumn={onDeleteColumn}
        />
      ))}

      {/* Tambah kolom */}
      <div className="w-64 shrink-0">
        {adding ? (
          <div className="rounded-3xl border border-border bg-white p-3 shadow-sm">
            <input
              autoFocus
              value={draft}
              onChange={(e) => setDraft(e.target.value)}
              onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); submitColumn(); } if (e.key === 'Escape') { setAdding(false); setDraft(''); } }}
              placeholder="Nama kolom status…"
              className="w-full rounded-xl border border-border px-3 py-2 text-sm font-semibold outline-none transition focus:border-indigo-500"
            />
            <div className="mt-2 flex gap-2">
              <button onClick={submitColumn} className="flex-1 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700">Tambah kolom</button>
              <button onClick={() => { setAdding(false); setDraft(''); }} className="rounded-xl px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-100">Batal</button>
            </div>
          </div>
        ) : (
          <button onClick={() => setAdding(true)} className="flex w-full items-center gap-2 rounded-3xl border border-dashed border-slate-300 bg-white/50 px-4 py-3 text-sm font-semibold text-slate-500 transition hover:border-indigo-400 hover:bg-white hover:text-indigo-600">
            <Plus size={16} /> Tambah kolom
          </button>
        )}
      </div>
    </div>
  );
}
