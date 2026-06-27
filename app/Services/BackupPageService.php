<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\BackupSetting;
use App\Repositories\BackupRepository;
use Carbon\CarbonImmutable;

/**
 * Menyusun data halaman backup & menyimpan setting.
 * Eksekusi backup tetap di DatabaseBackupService (runner).
 */
class BackupPageService
{
    public function __construct(private readonly BackupRepository $repo) {}

    public function indexData(): array
    {
        $setting = $this->repo->currentSetting();
        $lastSuccess = $this->repo->lastSuccess();

        return [
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
            'logs' => $this->repo->paginateLogs()->through(fn (BackupLog $log) => $this->serializeLog($log)),
            'summary' => [
                'last_success_at' => $lastSuccess?->finished_at?->toIso8601String(),
                'last_success_file' => $lastSuccess?->file_name,
                'next_run_at' => $setting->nextRunAfter(CarbonImmutable::now($setting->timezone ?: 'Asia/Jakarta'))?->toIso8601String(),
                'success_count' => $this->repo->successCount(),
                'failed_count' => $this->repo->failedCount(),
            ],
        ];
    }

    public function updateSetting(array $data, int $userId): void
    {
        $this->repo->saveSetting($data, $userId);
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
}
