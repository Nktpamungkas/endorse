<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use App\Models\EndorsementActivity;
use App\Models\EndorsementFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EndorsementFileController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $perPage = max(10, min((int) $request->integer('per_page', 10), 100));
        $baseQuery = EndorsementFile::query()
            ->with(['endorsement'])
            ->whereHas('endorsement', fn ($query) => $query->where('user_id', Auth::id()))
            ->latest();

        if ($request->filled('q')) {
            $keyword = (string) $request->string('q');
            $baseQuery->where(function ($query) use ($keyword): void {
                $query->where('original_name', 'like', '%'.$keyword.'%')
                    ->orWhereHas('endorsement', function ($endorsementQuery) use ($keyword): void {
                        $endorsementQuery->where('brand_name', 'like', '%'.$keyword.'%')
                            ->orWhere('campaign_name', 'like', '%'.$keyword.'%');
                    });
            });
        }

        if ($request->filled('endorsement_id')) {
            $baseQuery->where('endorsement_id', $request->integer('endorsement_id'));
        }

        if ($request->filled('category')) {
            $baseQuery->where('category', (string) $request->string('category'));
        }

        $summaryQuery = clone $baseQuery;
        $totalFiles = (clone $summaryQuery)->count();
        $totalSize = (int) (clone $summaryQuery)->sum('size_bytes');
        $endorsementsWithFiles = (clone $summaryQuery)->distinct('endorsement_id')->count('endorsement_id');
        $latestUpload = (clone $summaryQuery)->first();

        $files = $baseQuery
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (EndorsementFile $file) => $this->serializeFile($file));

        return Inertia::render('EndorsementFiles/Index', [
            'files' => $files,
            'filters' => [
                'q' => (string) $request->string('q'),
                'endorsement_id' => $request->filled('endorsement_id') ? (string) $request->integer('endorsement_id') : '',
                'category' => (string) $request->string('category'),
                'per_page' => $perPage,
            ],
            'stats' => [
                'total_files' => $totalFiles,
                'total_size' => $totalSize,
                'endorsements_with_files' => $endorsementsWithFiles,
                'latest_upload_at' => $latestUpload?->created_at?->toIso8601String(),
            ],
            'endorsementOptions' => Endorsement::withTrashed()
                ->where('user_id', Auth::id())
                ->orderByDesc('updated_at')
                ->get(['id', 'brand_name', 'campaign_name', 'deleted_at'])
                ->map(fn (Endorsement $endorsement) => [
                    'value' => (string) $endorsement->id,
                    'label' => trim($endorsement->brand_name.' - '.($endorsement->campaign_name ?: 'Tanpa campaign')),
                    'is_deleted' => $endorsement->trashed(),
                ])
                ->values(),
            'categoryOptions' => collect(EndorsementFile::CATEGORY_LABELS)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'maxUploadMb' => $this->maxUploadMb(),
            'maxFilesPerRequest' => max(1, (int) config('endorsement-files.max_files_per_request', 20)),
        ]);
    }

    public function store(Request $request, Endorsement $endorsement): RedirectResponse
    {
        $this->assertOwnership($endorsement);

        if ($endorsement->trashed()) {
            return back()->with('error', 'File baru tidak bisa diupload ke endorse yang sudah dibatalkan.');
        }

        $maxUploadMb = $this->maxUploadMb();
        $maxFilesPerRequest = max(1, (int) config('endorsement-files.max_files_per_request', 20));

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:'.$maxFilesPerRequest],
            'files.*' => ['required', 'file', 'max:'.($maxUploadMb * 1024)],
        ]);

        $disk = (string) config('endorsement-files.disk', 'local');
        $baseDirectory = trim((string) config('endorsement-files.directory', 'endorsement-files'), '/');
        $directory = $baseDirectory.'/'.$endorsement->id.'/'.now()->format('Y/m');
        $uploadedFiles = [];

        foreach ($data['files'] as $upload) {
            $extension = $upload->getClientOriginalExtension();
            $storedName = (string) Str::uuid().($extension ? '.'.Str::lower($extension) : '');
            $storedPath = $upload->storeAs($directory, $storedName, $disk);

            $endorsementFile = EndorsementFile::create([
                'endorsement_id' => $endorsement->id,
                'uploaded_by' => Auth::id(),
                'disk' => $disk,
                'directory' => dirname($storedPath),
                'stored_name' => basename($storedPath),
                'original_name' => $upload->getClientOriginalName(),
                'extension' => $extension ? Str::lower($extension) : null,
                'mime_type' => $upload->getMimeType(),
                'category' => $this->detectCategory($upload->getMimeType(), $extension),
                'size_bytes' => (int) $upload->getSize(),
                'sha256_checksum' => hash_file('sha256', $upload->getRealPath()) ?: null,
            ]);

            $uploadedFiles[] = $endorsementFile->original_name;
        }

        $this->logActivity($endorsement, 'file_upload', [
            'files' => $uploadedFiles,
            'count' => count($uploadedFiles),
        ]);

        return back()->with('success', count($uploadedFiles).' file berhasil diupload tanpa kompresi.');
    }

    public function preview(EndorsementFile $endorsementFile): BinaryFileResponse
    {
        $this->assertFileAccess($endorsementFile);

        $path = $this->absolutePath($endorsementFile);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => $endorsementFile->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-transform',
        ]);
    }

    public function download(EndorsementFile $endorsementFile): BinaryFileResponse
    {
        $this->assertFileAccess($endorsementFile);

        $path = $this->absolutePath($endorsementFile);
        abort_unless(is_file($path), 404);

        return response()->download($path, $endorsementFile->original_name, [
            'Content-Type' => $endorsementFile->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-transform',
        ]);
    }

    public function destroy(EndorsementFile $endorsementFile): RedirectResponse
    {
        $this->assertFileAccess($endorsementFile);

        $storagePath = $this->storagePath($endorsementFile);
        Storage::disk($endorsementFile->disk)->delete($storagePath);

        $endorsement = $endorsementFile->endorsement;
        $originalName = $endorsementFile->original_name;
        $endorsementFile->delete();

        if ($endorsement) {
            $this->logActivity($endorsement, 'file_delete', [
                'file' => $originalName,
            ]);
        }

        return back()->with('success', 'File berhasil dihapus dari penyimpanan.');
    }

    private function serializeFile(EndorsementFile $file): array
    {
        $endorsement = $file->endorsement;
        $campaignName = $endorsement?->campaign_name ?: 'Tanpa campaign';

        return [
            'id' => $file->id,
            'endorsement_id' => $file->endorsement_id,
            'endorsement_label' => trim(($endorsement?->brand_name ?: 'Endorse').' - '.$campaignName),
            'endorsement_brand_name' => $endorsement?->brand_name,
            'endorsement_campaign_name' => $endorsement?->campaign_name,
            'endorsement_deleted' => $endorsement?->trashed() ?? false,
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

    private function detectCategory(?string $mimeType, ?string $extension): string
    {
        $mime = Str::lower((string) $mimeType);
        $ext = Str::lower((string) $extension);

        if (Str::startsWith($mime, 'image/')) {
            return 'image';
        }

        if (Str::startsWith($mime, 'video/')) {
            return 'video';
        }

        if (Str::startsWith($mime, 'audio/')) {
            return 'audio';
        }

        if ($mime === 'application/pdf' || $ext === 'pdf') {
            return 'pdf';
        }

        if (
            Str::contains($mime, ['word', 'excel', 'sheet', 'presentation', 'officedocument']) ||
            in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'rtf'], true)
        ) {
            return 'document';
        }

        if (
            Str::contains($mime, ['zip', 'compressed', 'rar', 'tar', '7z']) ||
            in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'], true)
        ) {
            return 'archive';
        }

        return 'other';
    }

    private function storagePath(EndorsementFile $endorsementFile): string
    {
        return trim($endorsementFile->directory.'/'.$endorsementFile->stored_name, '/');
    }

    private function absolutePath(EndorsementFile $endorsementFile): string
    {
        return Storage::disk($endorsementFile->disk)->path($this->storagePath($endorsementFile));
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

        if ((int) $ownerId !== (int) $user->id && $user->role !== 'master') {
            redirect()->route('endorsements.index')
                ->withErrors(['access' => 'Data ini milik akun lain.'])
                ->throwResponse();
        }
    }

    private function assertFileAccess(EndorsementFile $endorsementFile): void
    {
        $endorsementFile->loadMissing('endorsement');
        abort_unless($endorsementFile->endorsement, 404);

        $this->assertOwnership($endorsementFile->endorsement);
    }

    private function maxUploadMb(): int
    {
        return max(1, (int) config('endorsement-files.max_upload_mb', 512));
    }
}
