/**
 * Konstanta domain Kanban Endorsement + formatter.
 * Kunci platform & payment_status disesuaikan dengan nilai backend Laravel.
 */

// Platform — kunci sesuai Endorsement::PLATFORM_OPTIONS di backend
export const PLATFORMS = {
  instagram:        { label: 'Instagram',    badge: 'bg-pink-50 text-pink-700 ring-pink-100' },
  tiktok:           { label: 'TikTok',       badge: 'bg-slate-100 text-slate-700 ring-slate-200' },
  tiktok_instagram: { label: 'TikTok+IG',   badge: 'bg-purple-50 text-purple-700 ring-purple-100' },
  owning_content:   { label: 'Owning',       badge: 'bg-amber-50 text-amber-700 ring-amber-100' },
};

// Status pembayaran — kunci sesuai Endorsement::PAYMENT_STATUS_OPTIONS di backend
export const PAYMENT_STATUS = {
  belum_bayar: { label: 'Belum Dibayar', dot: 'bg-rose-500',    badge: 'bg-rose-50 text-rose-700 ring-rose-200' },
  dp:          { label: 'DP / Sebagian', dot: 'bg-amber-500',   badge: 'bg-amber-50 text-amber-700 ring-amber-200' },
  lunas:       { label: 'Lunas',         dot: 'bg-emerald-500', badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
};

// Warna aksen kolom — dipakai frontend saat preview optimistik sebelum server reply
export const COLUMN_ACCENTS = ['#6366f1', '#0ea5e9', '#a855f7', '#f59e0b', '#10b981', '#ec4899', '#14b8a6', '#ef4444'];

export const formatRupiah = (n) => 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');

export function formatDate(iso) {
  if (!iso) return '';
  const M = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const d = new Date(iso);
  if (isNaN(d)) return '';
  return `${d.getDate()} ${M[d.getMonth()]}`;
}
