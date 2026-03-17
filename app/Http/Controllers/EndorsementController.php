<?php

namespace App\Http\Controllers;

use App\Http\Requests\EndorsementRequest;
use App\Models\Endorsement;
use App\Models\EndorsementActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class EndorsementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
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

        $endorsements = $query->paginate(50)->withQueryString();

        return view('endorsements.index', [
            'endorsements' => $endorsements,
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'insightFilter' => $insightFilter,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('endorsements.create', [
            'endorsement' => new Endorsement(),
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
            'contentTypeOptions' => Endorsement::CONTENT_TYPE_OPTIONS,
            'financialModeOptions' => Endorsement::FINANCIAL_MODE_OPTIONS,
            'paymentStatusOptions' => Endorsement::PAYMENT_STATUS_OPTIONS,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
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

    /**
     * Display the specified resource.
     */
    public function show(Endorsement $endorsement): View
    {
        $this->assertOwnership($endorsement);
        $endorsement->load('revisions');

        return view('endorsements.show', [
            'endorsement' => $endorsement,
            'statusOptions' => Endorsement::STATUS_OPTIONS,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Endorsement $endorsement): View
    {
        $this->assertOwnership($endorsement);
        return view('endorsements.edit', [
            'endorsement' => $endorsement,
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
            'contentTypeOptions' => Endorsement::CONTENT_TYPE_OPTIONS,
            'financialModeOptions' => Endorsement::FINANCIAL_MODE_OPTIONS,
            'paymentStatusOptions' => Endorsement::PAYMENT_STATUS_OPTIONS,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
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

    /**
     * Remove the specified resource from storage.
     */
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

    public function trashed(Request $request): View
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

        $endorsements = $query->paginate(50)->withQueryString();

        return view('endorsements.trashed', [
            'endorsements' => $endorsements,
        ]);
    }

    public function trashedShow(int $endorsementId): View
    {
        $endorsement = Endorsement::onlyTrashed()->with(['revisions', 'deletedBy'])->findOrFail($endorsementId);
        $this->assertOwnership($endorsement);

        return view('endorsements.show', [
            'endorsement' => $endorsement,
            'statusOptions' => Endorsement::STATUS_OPTIONS,
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
