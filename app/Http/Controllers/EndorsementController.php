<?php

namespace App\Http\Controllers;

use App\Http\Requests\EndorsementRequest;
use App\Models\Endorsement;
use App\Models\EndorsementActivity;
use App\Models\EndorsementFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EndorsementController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = max(5, min((int) $request->integer('per_page', 10), 100));

        $query = Endorsement::query()
            ->where('user_id', Auth::id())
            ->orderByRaw('CASE WHEN deal_date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('deal_date')
            ->orderByDesc('updated_at');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('brand_name', 'like', '%'.$keyword.'%')
                    ->orWhere('campaign_name', 'like', '%'.$keyword.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status'));
        }

        $insightFilter = (string) $request->string('insight');
        if ($insightFilter !== '') {
            if ($insightFilter === 'waiting') {
                $query->whereNotNull('insight_due_at')->whereNull('insight_sent_at');
            }

            if ($insightFilter === 'overdue') {
                $query->whereDate('insight_due_at', '<', Carbon::today())
                    ->whereNull('insight_sent_at');
            }

            if ($insightFilter === 'sent') {
                $query->whereNotNull('insight_sent_at');
            }
        }

        $endorsements = $query->paginate($perPage)->withQueryString()
            ->through(fn (Endorsement $endorsement) => $this->serializeListItem($endorsement));

        return Inertia::render('Endorsements/Index', [
            'endorsements' => $endorsements,
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'paymentStatusOptions' => Endorsement::PAYMENT_STATUS_OPTIONS,
            'filters' => [
                'q' => (string) $request->string('q'),
                'status' => (string) $request->string('status'),
                'payment_status' => (string) $request->string('payment_status'),
                'insight' => $insightFilter,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Endorsements/Create', [
            'endorsement' => $this->serializeFormEndorsement(new Endorsement()),
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
            'contentTypeOptions' => Endorsement::CONTENT_TYPE_OPTIONS,
            'financialModeOptions' => Endorsement::FINANCIAL_MODE_OPTIONS,
            'paymentStatusOptions' => Endorsement::PAYMENT_STATUS_OPTIONS,
        ]);
    }

    public function store(EndorsementRequest $request): RedirectResponse
    {
        $payload = $this->buildPayload($request);
        $payload['user_id'] = Auth::id();
        $fingerprint = sha1(json_encode([
            'brand_name' => (string) ($payload['brand_name'] ?? ''),
            'campaign_name' => (string) ($payload['campaign_name'] ?? ''),
            'platform' => (string) ($payload['platform'] ?? ''),
            'content_type' => (string) ($payload['content_type'] ?? ''),
            'status' => (string) ($payload['status'] ?? ''),
            'deal_date' => (string) ($payload['deal_date'] ?? ''),
            'posting_date' => (string) ($payload['posting_date'] ?? ''),
            'posted_at' => (string) ($payload['posted_at'] ?? ''),
            'payment_status' => (string) ($payload['payment_status'] ?? ''),
            'fee_amount' => (string) ($payload['fee_amount'] ?? 0),
            'reimburse_amount' => (string) ($payload['reimburse_amount'] ?? 0),
            'product_cost' => (string) ($payload['product_cost'] ?? 0),
            'other_cost' => (string) ($payload['other_cost'] ?? 0),
        ]));

        $lastFingerprint = (string) $request->session()->get('endorsement_store_fingerprint', '');
        $lastAt = (int) $request->session()->get('endorsement_store_fingerprint_at', 0);

        if ($lastFingerprint !== '' && $lastFingerprint === $fingerprint && (time() - $lastAt) <= 15) {
            return redirect()->route('endorsements.index')->with('success', 'Data sudah tersimpan. Submit ganda diabaikan.');
        }

        $endorsement = Endorsement::create($payload);
        $this->logActivity($endorsement, 'create', ['status' => $endorsement->status]);
        $request->session()->put('endorsement_store_fingerprint', $fingerprint);
        $request->session()->put('endorsement_store_fingerprint_at', time());

        return redirect()->route('endorsements.index')->with('success', 'Endorse berhasil ditambahkan.');
    }

    public function show(Endorsement $endorsement): Response
    {
        $this->assertOwnership($endorsement);
        $endorsement->load(['revisions', 'deletedBy', 'files'])->loadCount('files')->loadSum('files', 'size_bytes');

        return Inertia::render('Endorsements/Show', [
            'endorsement' => $this->serializeDetailEndorsement($endorsement),
            'revisions' => $endorsement->revisions->map(fn ($revision) => [
                'id' => $revision->id,
                'revision_date' => optional($revision->revision_date)->format('Y-m-d'),
                'uploaded_to_drive' => (bool) $revision->uploaded_to_drive,
                'is_approved' => (bool) $revision->is_approved,
                'note' => $revision->note,
            ])->values(),
            'logs' => $endorsement->activities()->limit(15)->get()->map(
                fn (EndorsementActivity $log) => $this->serializeLog($log)
            )->values(),
            'files' => $endorsement->files->take(8)->map(fn (EndorsementFile $file) => $this->serializeEndorsementFile($file))->values(),
            'fileSummary' => [
                'total_count' => (int) ($endorsement->files_count ?? 0),
                'total_size' => (int) ($endorsement->files_sum_size_bytes ?? 0),
                'showing_count' => min(8, (int) ($endorsement->files_count ?? 0)),
            ],
            'maxUploadMb' => max(1, (int) config('endorsement-files.max_upload_mb', 512)),
            'isDeletedView' => false,
        ]);
    }

    public function edit(Endorsement $endorsement): Response
    {
        $this->assertOwnership($endorsement);

        return Inertia::render('Endorsements/Edit', [
            'endorsement' => $this->serializeFormEndorsement($endorsement),
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
            'contentTypeOptions' => Endorsement::CONTENT_TYPE_OPTIONS,
            'financialModeOptions' => Endorsement::FINANCIAL_MODE_OPTIONS,
            'paymentStatusOptions' => Endorsement::PAYMENT_STATUS_OPTIONS,
        ]);
    }

    public function update(EndorsementRequest $request, Endorsement $endorsement): RedirectResponse
    {
        $this->assertOwnership($endorsement);
        $payload = $this->buildPayload($request, $endorsement);
        $original = $endorsement->getOriginal();
        $endorsement->fill($payload);
        $dirtyKeys = array_keys($endorsement->getDirty());
        $changes = [];
        foreach ($dirtyKeys as $key) {
            $changes[$key] = [
                'from' => $original[$key] ?? null,
                'to' => $endorsement->{$key},
            ];
        }
        $endorsement->save();
        $this->logActivity($endorsement, 'update', [
            'status' => $endorsement->status,
            'fields_changed' => $dirtyKeys,
            'changes' => $changes,
        ]);

        return redirect()->route('endorsements.show', $endorsement)->with('success', 'Endorse berhasil diupdate.');
    }

    public function destroy(Request $request, Endorsement $endorsement): RedirectResponse
    {
        $this->assertOwnership($endorsement);
        $data = $request->validate([
            'delete_reason' => ['required', 'string', 'max:500'],
        ]);

        $endorsement->forceFill([
            'deleted_reason' => $data['delete_reason'],
            'deleted_by' => Auth::id(),
        ])->save();

        $this->logActivity($endorsement, 'delete', [
            'status' => $endorsement->status,
            'reason' => $data['delete_reason'],
        ]);
        $endorsement->delete();

        return redirect()->route('endorsements.trashed')->with('success', 'Endorse dipindah ke arsip hapus.');
    }

    public function trashed(Request $request): Response
    {
        $query = Endorsement::onlyTrashed()
            ->where('user_id', Auth::id())
            ->with('deletedBy')
            ->orderByDesc('deleted_at');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('brand_name', 'like', '%'.$keyword.'%')
                    ->orWhere('campaign_name', 'like', '%'.$keyword.'%');
            });
        }

        $endorsements = $query->paginate(20)->withQueryString()
            ->through(fn (Endorsement $endorsement) => $this->serializeTrashedItem($endorsement));

        return Inertia::render('Endorsements/Trashed', [
            'endorsements' => $endorsements,
            'filters' => [
                'q' => (string) $request->string('q'),
            ],
        ]);
    }

    public function trashedShow(int $endorsementId): Response
    {
        $endorsement = Endorsement::onlyTrashed()->with(['revisions', 'deletedBy', 'files'])->findOrFail($endorsementId);
        $this->assertOwnership($endorsement);
        $endorsement->loadCount('files')->loadSum('files', 'size_bytes');

        return Inertia::render('Endorsements/Show', [
            'endorsement' => $this->serializeDetailEndorsement($endorsement),
            'revisions' => $endorsement->revisions->map(fn ($revision) => [
                'id' => $revision->id,
                'revision_date' => optional($revision->revision_date)->format('Y-m-d'),
                'uploaded_to_drive' => (bool) $revision->uploaded_to_drive,
                'is_approved' => (bool) $revision->is_approved,
                'note' => $revision->note,
            ])->values(),
            'logs' => $endorsement->activities()->limit(15)->get()->map(
                fn (EndorsementActivity $log) => $this->serializeLog($log)
            )->values(),
            'files' => $endorsement->files->take(8)->map(fn (EndorsementFile $file) => $this->serializeEndorsementFile($file))->values(),
            'fileSummary' => [
                'total_count' => (int) ($endorsement->files_count ?? 0),
                'total_size' => (int) ($endorsement->files_sum_size_bytes ?? 0),
                'showing_count' => min(8, (int) ($endorsement->files_count ?? 0)),
            ],
            'maxUploadMb' => max(1, (int) config('endorsement-files.max_upload_mb', 512)),
            'isDeletedView' => true,
        ]);
    }

    public function updateStatus(Request $request, Endorsement $endorsement): RedirectResponse
    {
        $this->assertOwnership($endorsement);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Endorsement::STATUS_OPTIONS))],
        ]);

        $old = $endorsement->status;
        $endorsement->update([
            'status' => $data['status'],
        ]);
        $this->logActivity($endorsement, 'status_change', ['from' => $old, 'to' => $data['status']]);

        return back()->with('success', 'Status berhasil diupdate.');
    }

    public function export(Request $request)
    {
        $query = Endorsement::where('user_id', Auth::id())->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('insight')) {
            $insightFilter = (string) $request->string('insight');
            if ($insightFilter === 'waiting') {
                $query->whereNotNull('insight_due_at')->whereNull('insight_sent_at');
            } elseif ($insightFilter === 'overdue') {
                $query->whereDate('insight_due_at', '<', Carbon::today())->whereNull('insight_sent_at');
            } elseif ($insightFilter === 'sent') {
                $query->whereNotNull('insight_sent_at');
            }
        }

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('brand_name', 'like', '%'.$keyword.'%')
                    ->orWhere('campaign_name', 'like', '%'.$keyword.'%');
            });
        }

        $rows = $query->get();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=\"endorsements.csv\"',
        ];
        $columns = ['Brand', 'Campaign', 'Platform', 'Status', 'Posting', 'Insight Due', 'Payment', 'Fee', 'Reimburse', 'Modal Produk', 'Biaya Lain', 'Laba Bersih'];

        return response()->stream(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->brand_name,
                    $row->campaign_name,
                    Endorsement::PLATFORM_OPTIONS[$row->platform] ?? $row->platform,
                    Endorsement::STATUS_OPTIONS[$row->status] ?? $row->status,
                    optional($row->posting_date)->format('Y-m-d'),
                    optional($row->insight_due_at)->format('Y-m-d'),
                    Endorsement::PAYMENT_STATUS_OPTIONS[$row->payment_status] ?? $row->payment_status,
                    $row->fee_amount,
                    $row->reimburse_amount,
                    $row->product_cost,
                    $row->other_cost,
                    $row->net_profit,
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    private function serializeListItem(Endorsement $endorsement): array
    {
        return [
            'id' => $endorsement->id,
            'brand_name' => $endorsement->brand_name,
            'campaign_name' => $endorsement->campaign_name,
            'platform' => $endorsement->platform,
            'platform_label' => Endorsement::PLATFORM_OPTIONS[$endorsement->platform] ?? $endorsement->platform,
            'status' => $endorsement->status,
            'status_label' => Endorsement::STATUS_OPTIONS[$endorsement->status] ?? $endorsement->status,
            'posting_date' => optional($endorsement->posting_date)->format('Y-m-d'),
            'insight_due_at' => optional($endorsement->insight_due_at)->format('Y-m-d'),
            'insight_sent_at' => optional($endorsement->insight_sent_at)->format('Y-m-d'),
            'is_insight_overdue' => $endorsement->insight_due_at?->isPast() && ! $endorsement->insight_sent_at,
            'payment_status' => $endorsement->payment_status,
            'payment_status_label' => Endorsement::PAYMENT_STATUS_OPTIONS[$endorsement->payment_status] ?? $endorsement->payment_status,
            'product_cost' => (float) $endorsement->product_cost,
            'other_cost' => (float) $endorsement->other_cost,
            'total_cost' => (float) $endorsement->total_cost,
            'net_profit' => (float) $endorsement->net_profit,
        ];
    }

    private function serializeTrashedItem(Endorsement $endorsement): array
    {
        return [
            'id' => $endorsement->id,
            'brand_name' => $endorsement->brand_name,
            'campaign_name' => $endorsement->campaign_name,
            'status_label' => Endorsement::STATUS_OPTIONS[$endorsement->status] ?? $endorsement->status,
            'deleted_at' => optional($endorsement->deleted_at)->toIso8601String(),
            'deleted_reason' => $endorsement->deleted_reason,
            'deleted_by_name' => optional($endorsement->deletedBy)->username,
        ];
    }

    private function serializeFormEndorsement(Endorsement $endorsement): array
    {
        return [
            'id' => $endorsement->id,
            'brand_name' => $endorsement->brand_name,
            'campaign_name' => $endorsement->campaign_name,
            'platform' => $endorsement->platform,
            'content_type' => $endorsement->content_type,
            'status' => $endorsement->status,
            'deal_date' => optional($endorsement->deal_date)->format('Y-m-d'),
            'product_ordered_at' => optional($endorsement->product_ordered_at)->format('Y-m-d'),
            'product_received_at' => optional($endorsement->product_received_at)->format('Y-m-d'),
            'draft_deadline' => optional($endorsement->draft_deadline)->format('Y-m-d'),
            'storyline_required' => (bool) $endorsement->storyline_required,
            'storyline_done' => (bool) $endorsement->storyline_done,
            'drive_uploaded' => (bool) $endorsement->drive_uploaded,
            'approved_at' => optional($endorsement->approved_at)->format('Y-m-d'),
            'posting_date' => optional($endorsement->posting_date)->format('Y-m-d'),
            'posted_at' => optional($endorsement->posted_at)->format('Y-m-d'),
            'insight_due_at' => optional($endorsement->insight_due_at)->format('Y-m-d'),
            'insight_sent_at' => optional($endorsement->insight_sent_at)->format('Y-m-d'),
            'boostcode_required' => (bool) $endorsement->boostcode_required,
            'boostcode_duration_days' => $endorsement->boostcode_duration_days,
            'self_purchase' => (bool) $endorsement->self_purchase,
            'checkout_proof_url' => $endorsement->checkout_proof_path ? asset('storage/'.$endorsement->checkout_proof_path) : null,
            'financial_mode' => $endorsement->financial_mode,
            'fee_amount' => (string) ($endorsement->fee_amount ?? ''),
            'reimburse_amount' => (string) ($endorsement->reimburse_amount ?? ''),
            'product_cost' => (string) ($endorsement->product_cost ?? ''),
            'other_cost' => (string) ($endorsement->other_cost ?? ''),
            'payment_status' => $endorsement->payment_status,
            'payment_due_date' => optional($endorsement->payment_due_date)->format('Y-m-d'),
            'payment_received_date' => optional($endorsement->payment_received_date)->format('Y-m-d'),
            'notes' => $endorsement->notes,
        ];
    }

    private function serializeDetailEndorsement(Endorsement $endorsement): array
    {
        return [
            ...$this->serializeFormEndorsement($endorsement),
            'platform_label' => Endorsement::PLATFORM_OPTIONS[$endorsement->platform] ?? $endorsement->platform,
            'content_type_label' => Endorsement::CONTENT_TYPE_OPTIONS[$endorsement->content_type] ?? $endorsement->content_type,
            'status_label' => Endorsement::STATUS_OPTIONS[$endorsement->status] ?? $endorsement->status,
            'storyline_text' => ! $endorsement->storyline_required
                ? 'Tidak perlu'
                : ($endorsement->storyline_done ? 'Perlu, sudah selesai' : 'Perlu, belum selesai'),
            'boostcode_text' => ! $endorsement->boostcode_required
                ? 'Tidak diminta'
                : trim(($endorsement->boostcode_duration_days ? $endorsement->boostcode_duration_days.' hari ' : '').($endorsement->boostcode_deadline ? '(sampai '.$endorsement->boostcode_deadline->format('d/m/Y').')' : '')),
            'financial_mode_label' => Endorsement::FINANCIAL_MODE_OPTIONS[$endorsement->financial_mode] ?? $endorsement->financial_mode,
            'payment_status_label' => Endorsement::PAYMENT_STATUS_OPTIONS[$endorsement->payment_status] ?? $endorsement->payment_status,
            'total_income' => (float) $endorsement->total_income,
            'total_cost' => (float) $endorsement->total_cost,
            'net_profit' => (float) $endorsement->net_profit,
            'fee_amount' => (float) $endorsement->fee_amount,
            'reimburse_amount' => (float) $endorsement->reimburse_amount,
            'product_cost' => (float) $endorsement->product_cost,
            'other_cost' => (float) $endorsement->other_cost,
            'trashed' => $endorsement->trashed(),
            'deleted_reason' => $endorsement->deleted_reason,
            'deleted_at' => optional($endorsement->deleted_at)->toIso8601String(),
            'deleted_by_name' => optional($endorsement->deletedBy)->username,
            'file_count' => (int) ($endorsement->files_count ?? 0),
            'file_total_size' => (int) ($endorsement->files_sum_size_bytes ?? 0),
        ];
    }

    private function serializeEndorsementFile(EndorsementFile $file): array
    {
        return [
            'id' => $file->id,
            'endorsement_id' => $file->endorsement_id,
            'endorsement_label' => trim(($file->endorsement?->brand_name ?: 'Endorse').' - '.($file->endorsement?->campaign_name ?: 'Tanpa campaign')),
            'endorsement_brand_name' => $file->endorsement?->brand_name,
            'endorsement_campaign_name' => $file->endorsement?->campaign_name,
            'endorsement_deleted' => $file->endorsement?->trashed() ?? false,
            'original_name' => $file->original_name,
            'extension' => $file->extension,
            'mime_type' => $file->mime_type,
            'category' => $file->category,
            'category_label' => EndorsementFile::CATEGORY_LABELS[$file->category] ?? 'Lainnya',
            'size_bytes' => (int) $file->size_bytes,
            'uploaded_at' => $file->created_at?->toIso8601String(),
            'can_preview' => in_array($file->category, ['image', 'video', 'audio', 'pdf'], true),
            'preview_url' => '/endorsement-files/'.$file->id.'/preview',
            'download_url' => '/endorsement-files/'.$file->id.'/download',
            'delete_url' => '/endorsement-files/'.$file->id,
        ];
    }

    private function serializeLog(EndorsementActivity $log): array
    {
        $meta = is_array($log->meta) ? $log->meta : [];
        $lines = [];

        foreach ($meta as $key => $value) {
            if ($key === 'changes' && is_array($value)) {
                foreach ($value as $field => $change) {
                    $from = $change['from'] === null || $change['from'] === '' ? '-' : (string) $change['from'];
                    $to = $change['to'] === null || $change['to'] === '' ? '-' : (string) $change['to'];
                    $lines[] = $field.': '.$from.' -> '.$to;
                }

                continue;
            }

            $lines[] = $key.': '.(is_array($value) ? json_encode($value) : (string) $value);
        }

        return [
            'id' => $log->id,
            'action_label' => ucfirst(str_replace('_', ' ', $log->action)),
            'created_at' => $log->created_at?->toIso8601String(),
            'meta_lines' => $lines,
        ];
    }

    private function buildPayload(EndorsementRequest $request, ?Endorsement $endorsement = null): array
    {
        $payload = $request->validated();
        $payload['boostcode_required'] = $request->boolean('boostcode_required');
        $payload['self_purchase'] = $request->boolean('self_purchase');
        $payload['storyline_required'] = $request->boolean('storyline_required');
        $payload['storyline_done'] = $request->boolean('storyline_done');
        $payload['drive_uploaded'] = $request->boolean('drive_uploaded');
        $payload['fee_amount'] = $payload['fee_amount'] ?? 0;
        $payload['reimburse_amount'] = $payload['reimburse_amount'] ?? 0;
        $payload['product_cost'] = $payload['product_cost'] ?? 0;
        $payload['other_cost'] = $payload['other_cost'] ?? 0;

        $naModes = ['na_dikirim_brand', 'na_tanpa_produk'];

        if (! $payload['self_purchase']) {
            if (! in_array($payload['financial_mode'], $naModes, true)) {
                $payload['financial_mode'] = 'na_dikirim_brand';
            }
            $payload['reimburse_amount'] = 0;
            $payload['product_cost'] = 0;
        }

        if ($payload['self_purchase'] && in_array($payload['financial_mode'], $naModes, true)) {
            $payload['financial_mode'] = 'reimburse_duluan';
        }

        if ($payload['financial_mode'] !== 'reimburse_duluan') {
            $payload['reimburse_amount'] = 0;
        }

        if ($payload['financial_mode'] === 'free_barter') {
            $payload['fee_amount'] = 0;
        }

        if (! $payload['boostcode_required']) {
            $payload['boostcode_duration_days'] = null;
        }

        if (! $payload['self_purchase']) {
            $payload['checkout_proof_path'] = null;
            if ($endorsement && $endorsement->checkout_proof_path) {
                Storage::disk('public')->delete($endorsement->checkout_proof_path);
            }
        }

        if ($request->hasFile('checkout_proof')) {
            if ($endorsement && $endorsement->checkout_proof_path) {
                Storage::disk('public')->delete($endorsement->checkout_proof_path);
            }
            $payload['checkout_proof_path'] = $request->file('checkout_proof')->store('checkout-proofs', 'public');
        }

        return $payload;
    }

    private function logActivity(Endorsement $endorsement, string $action, array $meta = []): void
    {
        EndorsementActivity::create([
            'endorsement_id' => $endorsement->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'meta' => $meta,
        ]);
    }

    private function assertOwnership(Endorsement $endorsement): void
    {
        $user = Auth::user();
        $ownerId = $endorsement->user_id;
        $currentId = $user->id;

        if ($ownerId === null) {
            $endorsement->update(['user_id' => $currentId]);
            return;
        }

        if ((int) $ownerId !== (int) $currentId && $user->role !== 'master') {
            redirect()->route('endorsements.index')
                ->withErrors(['access' => 'Data ini milik akun lain.'])
                ->throwResponse();
        }
    }
}
