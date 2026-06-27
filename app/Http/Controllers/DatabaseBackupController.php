<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Services\BackupPageService;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __construct(
        private readonly BackupPageService $page,
        private readonly DatabaseBackupService $runner,
    ) {}

    public function index(): Response
    {
        $this->authorizeMaster();

        return Inertia::render('Backups/Index', $this->page->indexData());
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeMaster();

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'timezone' => ['required', 'string', 'max:64'],
            'run_time' => ['required', 'date_format:H:i'],
            'run_days' => ['required', 'array', 'min:1'],
            'run_days.*' => ['required', 'in:mon,tue,wed,thu,fri,sat,sun'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'keep_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $this->page->updateSetting($data, Auth::id());

        return back()->with('success', 'Jadwal backup berhasil disimpan.');
    }

    public function runNow(): RedirectResponse
    {
        $this->authorizeMaster();

        try {
            $log = $this->runner->run('manual', Auth::user());
        } catch (\Throwable $exception) {
            return back()->with('error', 'Backup gagal dijalankan: '.$exception->getMessage());
        }

        return back()->with('success', 'Backup manual selesai dibuat: '.$log->file_name);
    }

    public function download(BackupLog $backupLog): BinaryFileResponse
    {
        $this->authorizeMaster();

        if ($backupLog->status !== 'success' || ! $backupLog->file_path || ! is_file($backupLog->file_path)) {
            abort(404, 'File backup tidak ditemukan.');
        }

        return response()->download($backupLog->file_path, $backupLog->file_name);
    }

    private function authorizeMaster(): void
    {
        abort_if(! Auth::check() || Auth::user()->role !== 'master', 403);
    }
}
