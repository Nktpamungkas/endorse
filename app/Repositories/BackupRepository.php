<?php

namespace App\Repositories;

use App\Models\BackupLog;
use App\Models\BackupSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BackupRepository
{
    public function currentSetting(): BackupSetting
    {
        return BackupSetting::current();
    }

    public function saveSetting(array $data, int $userId): BackupSetting
    {
        $setting = BackupSetting::current();
        $setting->fill($data);
        $setting->updated_by = $userId;
        $setting->save();

        return $setting;
    }

    public function paginateLogs(int $perPage = 15): LengthAwarePaginator
    {
        return BackupLog::query()
            ->with('triggeredByUser:id,username')
            ->latest('started_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function lastSuccess(): ?BackupLog
    {
        return BackupLog::query()
            ->where('status', 'success')
            ->latest('finished_at')
            ->first();
    }

    public function successCount(): int
    {
        return BackupLog::query()->where('status', 'success')->count();
    }

    public function failedCount(): int
    {
        return BackupLog::query()->where('status', 'failed')->count();
    }
}
