<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Create a database backup file and store it locally';

    public function __construct(private readonly DatabaseBackupService $databaseBackupService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $log = $this->databaseBackupService->run('manual');

        $this->info('Backup selesai: '.$log->file_path);

        return self::SUCCESS;
    }
}
