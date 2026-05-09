<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Models\BackupSetting;
use App\Services\DatabaseBackupService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __construct(private readonly DatabaseBackupService $databaseBackupService)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorizeMaster();

        $setting = BackupSetting::current();
        $logs = BackupLog::query()
            ->with('triggeredByUser:id,username')
            ->latest('started_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (BackupLog $log) => $this->serializeLog($log));

        $lastSuccess = BackupLog::query()
            ->where('status', 'success')
            ->latest('finished_at')
            ->first();

        return Inertia::render('Backups/Index', [
            'setting' => [
                'enabled' => (bool) $setting->enabled,
                'timezone' => $setting->timezone,
                'run_time' => $setting->run_time,
                'run_days' => $setting->normalizedRunDays(),
                'start_date' => $setting->start_date?->format('Y-m-d'),
                'end_date' => $setting->end_date?->format('Y-m-d'),
                'keep_days' => (int) $setting->keep_days,
                'updated_at' => $setting->updated_at?->toIso8601String(),
                'updated_by' => $setting->updater?->username,
            ],
            'dayOptions' => BackupSetting::DAY_OPTIONS,
            'logs' => $logs,
            'summary' => [
                'last_success_at' => $lastSuccess?->finished_at?->toIso8601String(),
                'last_success_file' => $lastSuccess?->file_name,
                'next_run_at' => $setting->nextRunAfter(CarbonImmutable::now($setting->timezone ?: 'Asia/Jakarta'))?->toIso8601String(),
                'success_count' => BackupLog::query()->where('status', 'success')->count(),
                'failed_count' => BackupLog::query()->where('status', 'failed')->count(),
            ],
        ]);
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

        $setting = BackupSetting::current();
        $setting->fill($data);
        $setting->updated_by = Auth::id();
        $setting->save();

        return back()->with('success', 'Jadwal backup berhasil disimpan.');
    }

    public function runNow(): RedirectResponse
    {
        $this->authorizeMaster();

        try {
            $log = $this->databaseBackupService->run('manual', Auth::user());
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

    private function serializeLog(BackupLog $log): array
    {
        return [
            'id' => $log->id,
            'status' => $log->status,
            'trigger_type' => $log->trigger_type,
            'database_driver' => $log->database_driver,
            'timezone' => $log->timezone,
            'scheduled_for' => $log->scheduled_for?->toIso8601String(),
            'started_at' => $log->started_at?->toIso8601String(),
            'finished_at' => $log->finished_at?->toIso8601String(),
            'file_name' => $log->file_name,
            'file_size_bytes' => (int) ($log->file_size_bytes ?? 0),
            'message' => $log->message,
            'triggered_by' => $log->triggeredByUser?->username,
            'download_url' => $log->status === 'success' && $log->file_path && is_file($log->file_path)
                ? route('database-backups.download', $log)
                : null,
        ];
    }

    private function authorizeMaster(): void
    {
        if (! Auth::check() || Auth::user()->role !== 'master') {
            abort(403);
        }
    }
}
