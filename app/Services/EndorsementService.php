<?php

namespace App\Services;

use App\Models\Endorsement;
use App\Models\EndorsementActivity;
use App\Repositories\EndorsementRepository;
use App\Repositories\KanbanColumnRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Logika bisnis Endorsement: normalisasi finansial, ubah status (manual),
 * log activity, dan mapping ke bentuk array untuk frontend.
 * Tidak menyentuh Eloquent langsung — lewat EndorsementRepository.
 */
class EndorsementService
{
    public function __construct(
        private readonly EndorsementRepository $repo,
        private readonly KanbanColumnRepository $columnRepo,
    ) {}

    /** Susun kolom-kolom Kanban dari kolom dinamis user + kartu aktif. */
    public function board(int $userId, array $filters): array
    {
        $kanbanCols = $this->columnRepo->getOrSeed($userId);
        $cards = $this->repo->activeForBoard($userId, $filters)
            ->map(fn (Endorsement $e) => $this->toCard($e));

        return $kanbanCols->map(fn ($col) => [
            'id' => $col->slug,
            'name' => $col->name,
            'accent' => $col->accent,
            'cards' => $cards->where('status', $col->slug)->values()->all(),
        ])->values()->all();
    }

    public function create(array $input, ?UploadedFile $proof, int $userId): Endorsement
    {
        $payload = $this->normalizeFinancials($input, $proof, null);
        $payload['user_id'] = $userId;

        $endorsement = $this->repo->create($payload);
        $this->repo->logActivity($endorsement->id, $userId, 'create', ['status' => $endorsement->status]);

        return $endorsement;
    }

    public function update(Endorsement $endorsement, array $input, ?UploadedFile $proof, int $userId): Endorsement
    {
        $payload = $this->normalizeFinancials($input, $proof, $endorsement);

        $original = $endorsement->getOriginal();
        $endorsement->fill($payload);
        $dirty = array_keys($endorsement->getDirty());
        $changes = [];
        foreach ($dirty as $key) {
            $changes[$key] = ['from' => $original[$key] ?? null, 'to' => $endorsement->{$key}];
        }
        $this->repo->update($endorsement, $endorsement->getDirty());
        $this->repo->logActivity($endorsement->id, $userId, 'update', [
            'status' => $endorsement->status,
            'fields_changed' => $dirty,
            'changes' => $changes,
        ]);

        return $endorsement;
    }

    /** Drag kartu antar kolom = ubah status saja (manual murni, tanpa side-effect). */
    public function changeStatus(Endorsement $endorsement, string $status, int $userId): Endorsement
    {
        $old = $endorsement->status;
        $this->repo->update($endorsement, ['status' => $status]);
        $this->repo->logActivity($endorsement->id, $userId, 'status_change', ['from' => $old, 'to' => $status]);

        return $endorsement;
    }

    /** Edit cepat dari modal Kanban — subset field saja. */
    public function quickUpdate(Endorsement $endorsement, array $fields, int $userId): void
    {
        $allowed = ['brand_name', 'campaign_name', 'platform', 'payment_status', 'posting_date', 'notes'];
        $payload = array_filter($fields, fn ($k) => in_array($k, $allowed, true), ARRAY_FILTER_USE_KEY);
        if (empty($payload)) {
            return;
        }
        $this->repo->update($endorsement, $payload);
        $this->repo->logActivity($endorsement->id, $userId, 'update', [
            'fields_changed' => array_keys($payload),
        ]);
    }

    /** Buat endorsement minimal dari quick-add di kolom Kanban. */
    public function quickStore(string $brandName, string $status, int $userId): Endorsement
    {
        $e = $this->repo->create([
            'user_id' => $userId,
            'brand_name' => $brandName,
            'status' => $status,
            'platform' => 'instagram',
            'content_type' => 'video',
            'payment_status' => 'belum_bayar',
            'financial_mode' => 'na_dikirim_brand',
        ]);
        $this->repo->logActivity($e->id, $userId, 'create', ['status' => $status]);

        return $e;
    }

    public function delete(Endorsement $endorsement, string $reason, int $userId): void
    {
        $endorsement->forceFill(['deleted_reason' => $reason, 'deleted_by' => $userId])->save();
        $this->repo->logActivity($endorsement->id, $userId, 'delete', ['status' => $endorsement->status, 'reason' => $reason]);
        $endorsement->delete();
    }

    /**
     * Aturan finansial (dari buildPayload lama, TANPA inferensi status workflow).
     * Status diambil apa adanya dari input form.
     */
    private function normalizeFinancials(array $input, ?UploadedFile $proof, ?Endorsement $existing): array
    {
        // Hanya field yang masih ada di form yang dinormalisasi. Field yang sudah
        // dihapus dari form tidak disentuh di sini agar nilai lama tidak ketimpa saat edit.
        $p = $input;
        $p['self_purchase'] = (bool) ($input['self_purchase'] ?? false);
        $p['fee_amount'] = $p['fee_amount'] ?? 0;
        $p['reimburse_amount'] = $p['reimburse_amount'] ?? 0;
        $p['product_cost'] = $p['product_cost'] ?? 0;

        $naModes = ['na_dikirim_brand', 'na_tanpa_produk'];

        if (! $p['self_purchase']) {
            if (! in_array($p['financial_mode'], $naModes, true)) {
                $p['financial_mode'] = 'na_dikirim_brand';
            }
            $p['reimburse_amount'] = 0;
            $p['product_cost'] = 0;
        }

        if ($p['self_purchase'] && in_array($p['financial_mode'], $naModes, true)) {
            $p['financial_mode'] = 'reimburse_duluan';
        }

        if ($p['financial_mode'] !== 'reimburse_duluan') {
            $p['reimburse_amount'] = 0;
        }

        if ($p['financial_mode'] === 'free_barter') {
            $p['fee_amount'] = 0;
        }

        // Bukti checkout dibersihkan jika bukan beli sendiri (input upload sudah dihapus dari form).
        if (! $p['self_purchase'] && $existing && $existing->checkout_proof_path) {
            Storage::disk('public')->delete($existing->checkout_proof_path);
            $p['checkout_proof_path'] = null;
        }

        if ($proof) {
            if ($existing && $existing->checkout_proof_path) {
                Storage::disk('public')->delete($existing->checkout_proof_path);
            }
            $p['checkout_proof_path'] = $proof->store('checkout-proofs', 'public');
        }

        return $p;
    }

    // ---------- Mapping ke array untuk frontend ----------

    public function toCard(Endorsement $e): array
    {
        return [
            'id' => $e->id,
            'status' => $e->status,
            // Display (EndorsementCard)
            'brand' => $e->brand_name,
            'campaign' => $e->campaign_name,
            'platform' => $e->platform,
            'paymentStatus' => $e->payment_status,
            'value' => (float) $e->fee_amount,
            'profit' => (float) $e->net_profit,
            'deadline' => optional($e->posting_date)->format('Y-m-d'),
            'notes' => $e->notes,
            // Form fields untuk EditModal (tanpa request tambahan ke server)
            'content_type' => $e->content_type,
            'deal_date' => optional($e->deal_date)->format('Y-m-d'),
            'product_ordered_at' => optional($e->product_ordered_at)->format('Y-m-d'),
            'product_received_at' => optional($e->product_received_at)->format('Y-m-d'),
            'draft_deadline' => optional($e->draft_deadline)->format('Y-m-d'),
            'posting_date' => optional($e->posting_date)->format('Y-m-d'),
            'self_purchase' => (bool) $e->self_purchase,
            'financial_mode' => $e->financial_mode,
            'fee_amount' => (string) ($e->fee_amount ?? '0'),
            'reimburse_amount' => (string) ($e->reimburse_amount ?? '0'),
            'product_cost' => (string) ($e->product_cost ?? '0'),
            'showUrl' => "/endorsements/{$e->id}",
        ];
    }

    public function toTrashedItem(Endorsement $e): array
    {
        return [
            'id' => $e->id,
            'brand_name' => $e->brand_name,
            'campaign_name' => $e->campaign_name,
            'status_label' => Endorsement::STATUS_OPTIONS[$e->status] ?? $e->status,
            'deleted_at' => optional($e->deleted_at)->toIso8601String(),
            'deleted_reason' => $e->deleted_reason,
            'deleted_by_name' => optional($e->deletedBy)->username,
        ];
    }

    public function toForm(Endorsement $e): array
    {
        return [
            'id' => $e->id,
            'brand_name' => $e->brand_name,
            'campaign_name' => $e->campaign_name,
            'platform' => $e->platform,
            'content_type' => $e->content_type,
            'status' => $e->status,
            'deal_date' => optional($e->deal_date)->format('Y-m-d'),
            'product_ordered_at' => optional($e->product_ordered_at)->format('Y-m-d'),
            'product_received_at' => optional($e->product_received_at)->format('Y-m-d'),
            'draft_deadline' => optional($e->draft_deadline)->format('Y-m-d'),
            'storyline_required' => (bool) $e->storyline_required,
            'storyline_done' => (bool) $e->storyline_done,
            'drive_uploaded' => (bool) $e->drive_uploaded,
            'approved_at' => optional($e->approved_at)->format('Y-m-d'),
            'posting_date' => optional($e->posting_date)->format('Y-m-d'),
            'posted_at' => optional($e->posted_at)->format('Y-m-d'),
            'insight_due_at' => optional($e->insight_due_at)->format('Y-m-d'),
            'insight_sent_at' => optional($e->insight_sent_at)->format('Y-m-d'),
            'boostcode_required' => (bool) $e->boostcode_required,
            'boostcode_duration_days' => $e->boostcode_duration_days,
            'self_purchase' => (bool) $e->self_purchase,
            'checkout_proof_url' => $e->checkout_proof_path ? asset('storage/'.$e->checkout_proof_path) : null,
            'financial_mode' => $e->financial_mode,
            'fee_amount' => (string) ($e->fee_amount ?? ''),
            'reimburse_amount' => (string) ($e->reimburse_amount ?? ''),
            'product_cost' => (string) ($e->product_cost ?? ''),
            'other_cost' => (string) ($e->other_cost ?? ''),
            'payment_status' => $e->payment_status,
            'payment_due_date' => optional($e->payment_due_date)->format('Y-m-d'),
            'payment_received_date' => optional($e->payment_received_date)->format('Y-m-d'),
            'notes' => $e->notes,
        ];
    }

    public function toDetail(Endorsement $e): array
    {
        return [
            ...$this->toForm($e),
            'platform_label' => Endorsement::PLATFORM_OPTIONS[$e->platform] ?? $e->platform,
            'content_type_label' => Endorsement::CONTENT_TYPE_OPTIONS[$e->content_type] ?? $e->content_type,
            'status_label' => Endorsement::STATUS_OPTIONS[$e->status] ?? $e->status,
            'storyline_text' => ! $e->storyline_required
                ? 'Tidak perlu'
                : ($e->storyline_done ? 'Perlu, sudah selesai' : 'Perlu, belum selesai'),
            'boostcode_text' => ! $e->boostcode_required
                ? 'Tidak diminta'
                : trim(($e->boostcode_duration_days ? $e->boostcode_duration_days.' hari ' : '').($e->boostcode_deadline ? '(sampai '.$e->boostcode_deadline->format('d/m/Y').')' : '')),
            'financial_mode_label' => Endorsement::FINANCIAL_MODE_OPTIONS[$e->financial_mode] ?? $e->financial_mode,
            'payment_status_label' => Endorsement::PAYMENT_STATUS_OPTIONS[$e->payment_status] ?? $e->payment_status,
            'total_income' => (float) $e->total_income,
            'total_cost' => (float) $e->total_cost,
            'net_profit' => (float) $e->net_profit,
            'fee_amount' => (float) $e->fee_amount,
            'reimburse_amount' => (float) $e->reimburse_amount,
            'product_cost' => (float) $e->product_cost,
            'other_cost' => (float) $e->other_cost,
            'trashed' => $e->trashed(),
            'deleted_reason' => $e->deleted_reason,
            'deleted_at' => optional($e->deleted_at)->toIso8601String(),
            'deleted_by_name' => optional($e->deletedBy)->username,
        ];
    }

    public function mapRevisions(Endorsement $e): array
    {
        return $e->revisions->map(fn ($r) => [
            'id' => $r->id,
            'revision_date' => optional($r->revision_date)->format('Y-m-d'),
            'uploaded_to_drive' => (bool) $r->uploaded_to_drive,
            'is_approved' => (bool) $r->is_approved,
            'note' => $r->note,
        ])->values()->all();
    }

    public function mapLogs(Endorsement $e): array
    {
        return $this->repo->recentActivities($e)
            ->map(fn (EndorsementActivity $log) => $this->serializeLog($log))
            ->values()->all();
    }

    private function serializeLog(EndorsementActivity $log): array
    {
        $meta = is_array($log->meta) ? $log->meta : [];
        $lines = [];

        foreach ($meta as $key => $value) {
            if ($key === 'changes' && is_array($value)) {
                foreach ($value as $field => $change) {
                    $from = $this->formatLogValue($field, $change['from'] ?? null);
                    $to = $this->formatLogValue($field, $change['to'] ?? null);
                    $lines[] = $this->fieldLabel($field).': '.$from.' -> '.$to;
                }

                continue;
            }

            if ($key === 'from' || $key === 'to') {
                $lines[] = ($key === 'from' ? 'Dari' : 'Ke').': '.$this->formatLogValue('status', $value);

                continue;
            }

            $lines[] = $this->fieldLabel($key).': '.$this->formatLogValue($key, $value);
        }

        return [
            'id' => $log->id,
            'action_label' => $this->activityLabel($log->action),
            'created_at' => $log->created_at?->toIso8601String(),
            'meta_lines' => $lines,
        ];
    }

    private function activityLabel(string $action): string
    {
        return match ($action) {
            'create' => 'Data dibuat',
            'update' => 'Data diperbarui',
            'delete' => 'Data dibatalkan',
            'status_change' => 'Status diubah',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    private function fieldLabel(string $field): string
    {
        return [
            'brand_name' => 'Brand', 'campaign_name' => 'Campaign', 'platform' => 'Platform',
            'content_type' => 'Jenis konten', 'status' => 'Status', 'deal_date' => 'Tanggal deal',
            'product_ordered_at' => 'Order produk', 'product_received_at' => 'Produk diterima',
            'draft_deadline' => 'Deadline draft', 'storyline_required' => 'Perlu storyline',
            'storyline_done' => 'Storyline selesai', 'drive_uploaded' => 'Upload Drive',
            'approved_at' => 'Tanggal approved', 'posting_date' => 'Rencana posting',
            'posted_at' => 'Sudah posting', 'insight_due_at' => 'Jatuh tempo laporan',
            'insight_sent_at' => 'Laporan terkirim', 'boostcode_required' => 'Perlu boostcode',
            'boostcode_duration_days' => 'Durasi boostcode', 'self_purchase' => 'Beli produk sendiri',
            'financial_mode' => 'Skema finansial', 'fee_amount' => 'Fee', 'reimburse_amount' => 'Reimburse',
            'product_cost' => 'Modal produk', 'other_cost' => 'Biaya lain', 'payment_status' => 'Status pembayaran',
            'payment_due_date' => 'Jatuh tempo payment', 'payment_received_date' => 'Payment masuk',
            'notes' => 'Catatan', 'reason' => 'Alasan', 'fields_changed' => 'Field berubah',
        ][$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function formatLogValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => (string) $item, $value));
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        $stringValue = (string) $value;

        return match ($field) {
            'status' => Endorsement::STATUS_OPTIONS[$stringValue] ?? $stringValue,
            'platform' => Endorsement::PLATFORM_OPTIONS[$stringValue] ?? $stringValue,
            'content_type' => Endorsement::CONTENT_TYPE_OPTIONS[$stringValue] ?? $stringValue,
            'financial_mode' => Endorsement::FINANCIAL_MODE_OPTIONS[$stringValue] ?? $stringValue,
            'payment_status' => Endorsement::PAYMENT_STATUS_OPTIONS[$stringValue] ?? $stringValue,
            default => $stringValue,
        };
    }
}
