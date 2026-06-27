<?php

namespace App\Http\Controllers;

use App\Http\Requests\EndorsementRequest;
use App\Models\Endorsement;
use App\Repositories\EndorsementRepository;
use App\Services\EndorsementService;
use App\Services\KanbanColumnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EndorsementController extends Controller
{
    public function __construct(
        private readonly EndorsementService $service,
        private readonly EndorsementRepository $repo,
        private readonly KanbanColumnService $columnService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        return Inertia::render('Endorsements/Index', [
            'columns' => $this->service->board(Auth::id(), $filters),
            'statusOptions' => $this->columnService->optionsFor(Auth::id()),
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
            'contentTypeOptions' => Endorsement::CONTENT_TYPE_OPTIONS,
            'financialModeOptions' => Endorsement::FINANCIAL_MODE_OPTIONS,
            'paymentStatusOptions' => Endorsement::PAYMENT_STATUS_OPTIONS,
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Endorsements/Create', $this->formProps(new Endorsement));
    }

    public function store(EndorsementRequest $request): RedirectResponse
    {
        $this->service->create(
            $request->safe()->except('checkout_proof'),
            $request->file('checkout_proof'),
            Auth::id(),
        );

        return redirect()->route('endorsements.index')->with('success', 'Endorse berhasil ditambahkan.');
    }

    public function show(Endorsement $endorsement): Response
    {
        $this->authorizeOwnership($endorsement);
        $endorsement->load(['revisions', 'deletedBy']);

        return Inertia::render('Endorsements/Show', [
            'endorsement' => $this->service->toDetail($endorsement),
            'revisions' => $this->service->mapRevisions($endorsement),
            'logs' => $this->service->mapLogs($endorsement),
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'isDeletedView' => false,
        ]);
    }

    public function edit(Endorsement $endorsement): Response
    {
        $this->authorizeOwnership($endorsement);

        return Inertia::render('Endorsements/Edit', $this->formProps($endorsement));
    }

    public function update(EndorsementRequest $request, Endorsement $endorsement): RedirectResponse
    {
        $this->authorizeOwnership($endorsement);
        $this->service->update(
            $endorsement,
            $request->safe()->except('checkout_proof'),
            $request->file('checkout_proof'),
            Auth::id(),
        );

        return redirect()->route('endorsements.index')->with('success', 'Endorse berhasil diupdate.');
    }

    public function destroy(Request $request, Endorsement $endorsement): RedirectResponse
    {
        $this->authorizeOwnership($endorsement);
        $data = $request->validate(['delete_reason' => ['required', 'string', 'max:500']]);
        $this->service->delete($endorsement, $data['delete_reason'], Auth::id());

        return redirect()->route('endorsements.trashed')->with('success', 'Endorse dipindah ke arsip hapus.');
    }

    public function updateStatus(Request $request, Endorsement $endorsement): RedirectResponse
    {
        $this->authorizeOwnership($endorsement);
        $validSlugs = $this->columnService->getOrSeed(Auth::id())->pluck('slug')->all();
        $data = $request->validate([
            'status' => ['required', Rule::in($validSlugs)],
        ]);
        $this->service->changeStatus($endorsement, $data['status'], Auth::id());

        return back()->with('success', 'Status berhasil diupdate.');
    }

    public function quickUpdate(Request $request, Endorsement $endorsement): RedirectResponse
    {
        $this->authorizeOwnership($endorsement);
        $data = $request->validate([
            'brand_name' => ['sometimes', 'string', 'max:100'],
            'campaign_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'platform' => ['sometimes', Rule::in(array_keys(Endorsement::PLATFORM_OPTIONS))],
            'payment_status' => ['sometimes', Rule::in(array_keys(Endorsement::PAYMENT_STATUS_OPTIONS))],
            'posting_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);
        $this->service->quickUpdate($endorsement, $data, Auth::id());

        return back();
    }

    public function quickStore(Request $request): RedirectResponse
    {
        $validSlugs = $this->columnService->getOrSeed(Auth::id())->pluck('slug')->all();
        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in($validSlugs)],
        ]);
        $this->service->quickStore($data['brand_name'], $data['status'], Auth::id());

        return back();
    }

    public function trashed(Request $request): Response
    {
        $items = $this->repo->trashedForUser(Auth::id(), ['q' => (string) $request->string('q')])
            ->map(fn (Endorsement $e) => $this->service->toTrashedItem($e))
            ->values();

        return Inertia::render('Endorsements/Trashed', [
            'endorsements' => $items,
            'filters' => ['q' => (string) $request->string('q')],
        ]);
    }

    public function trashedShow(int $endorsement): Response
    {
        $model = $this->repo->findTrashed($endorsement, Auth::id());
        abort_if($model === null, 404);

        return Inertia::render('Endorsements/Show', [
            'endorsement' => $this->service->toDetail($model),
            'revisions' => $this->service->mapRevisions($model),
            'logs' => $this->service->mapLogs($model),
            'statusOptions' => Endorsement::STATUS_OPTIONS,
            'isDeletedView' => true,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->repo->exportForUser(Auth::id(), $this->filters($request));
        $columns = ['Brand', 'Campaign', 'Platform', 'Status', 'Posting', 'Insight Due', 'Payment', 'Fee', 'Reimburse', 'Modal Produk', 'Biaya Lain', 'Laba Bersih'];

        return response()->stream(function () use ($rows, $columns): void {
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
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="endorsements.csv"',
        ]);
    }

    private function filters(Request $request): array
    {
        return [
            'q' => (string) $request->string('q'),
            'status' => (string) $request->string('status'),
            'payment_status' => (string) $request->string('payment_status'),
            'insight' => (string) $request->string('insight'),
        ];
    }

    private function formProps(Endorsement $endorsement): array
    {
        return [
            'endorsement' => $this->service->toForm($endorsement),
            'statusOptions' => $this->columnService->optionsFor(Auth::id()),  // dinamis dari kanban_columns
            'platformOptions' => Endorsement::PLATFORM_OPTIONS,
            'contentTypeOptions' => Endorsement::CONTENT_TYPE_OPTIONS,
            'financialModeOptions' => Endorsement::FINANCIAL_MODE_OPTIONS,
            'paymentStatusOptions' => Endorsement::PAYMENT_STATUS_OPTIONS,
        ];
    }

    /** Otorisasi: data milik user, atau role master. Klaim user_id null ke user aktif. */
    private function authorizeOwnership(Endorsement $endorsement): void
    {
        $user = Auth::user();

        if ($endorsement->user_id === null) {
            $endorsement->update(['user_id' => $user->id]);

            return;
        }

        if ((int) $endorsement->user_id !== (int) $user->id && $user->role !== 'master') {
            redirect()->route('endorsements.index')
                ->withErrors(['access' => 'Data ini milik akun lain.'])
                ->throwResponse();
        }
    }
}
