<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Models\BackupSetting;
use App\Services\DatabaseBackupService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RunScheduledDatabaseBackupCommand extends Command
{
    protected $signature = 'backup:scheduled-check';

    protected $description = 'Check backup schedule settings and run backup if it is due';

    public function __construct(private readonly DatabaseBackupService $databaseBackupService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! Schema::hasTable('backup_settings') || ! Schema::hasTable('backup_logs')) {
            return self::SUCCESS;
        }

        $setting = BackupSetting::current();
        $now = CarbonImmutable::now($setting->timezone ?: 'Asia/Jakarta')->startOfMinute();

        if (! $setting->isDueAt($now)) {
            return self::SUCCESS;
        }

        $scheduledFor = $now->setTimezone(config('app.timezone'));

        $alreadyLogged = BackupLog::query()
            ->where('trigger_type', 'scheduled')
            ->where('scheduled_for', $scheduledFor)
            ->exists();

        if ($alreadyLogged) {
            return self::SUCCESS;
        }

        $this->databaseBackupService->run('scheduled', null, $now, $setting);
        $this->info('Backup terjadwal dijalankan untuk '.$now->format('Y-m-d H:i'));

        return self::SUCCESS;
    }
}
