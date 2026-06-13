<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateToSqliteCommand extends Command
{
    protected $signature = 'endorse:migrate-to-sqlite
                            {--fresh : Hapus SQLite file yang sudah ada dan mulai dari awal}';

    protected $description = 'Migrasi semua data dari SQL Server ke SQLite';

    // Urutan tabel sesuai dependency FK
    private array $tables = [
        'users',
        'user_login_activities',
        'endorsements',
        'endorsement_revisions',
        'endorsement_activities',
        'pemasukan',
        'pengeluaran',
        'backup_settings',
        'backup_logs',
    ];

    // Tabel yang sengaja di-skip (tidak perlu dimigrasikan)
    private array $skipTables = [
        'migrations',   // otomatis diisi ulang saat migrate
        'sessions',     // session lama tidak relevan
        'cache',        // cache lama tidak relevan
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    public function handle(): int
    {
        $sqlitePath = database_path('database.sqlite');

        $this->info('=== Migrasi SQL Server → SQLite ===');
        $this->newLine();

        // Cek source connection
        try {
            DB::connection()->getPdo();
            $this->info('✓ Koneksi SQL Server berhasil');
        } catch (\Throwable $e) {
            $this->error('✗ Gagal konek ke SQL Server: '.$e->getMessage());
            $this->line('  Pastikan DB_CONNECTION=sqlsrv dan kredensial benar di .env');

            return self::FAILURE;
        }

        // Buat atau reset SQLite file
        if (file_exists($sqlitePath)) {
            if ($this->option('fresh')) {
                unlink($sqlitePath);
                $this->warn('SQLite file lama dihapus.');
            } else {
                if (! $this->confirm("File SQLite sudah ada di $sqlitePath. Lanjutkan? (data lama akan ditimpa)")) {
                    $this->line('Dibatalkan.');

                    return self::SUCCESS;
                }
            }
        }

        if (! file_exists($sqlitePath)) {
            touch($sqlitePath);
            $this->info("✓ SQLite file dibuat: $sqlitePath");
        }

        // Setup koneksi SQLite target
        config(['database.connections.sqlite_target' => [
            'driver' => 'sqlite',
            'database' => $sqlitePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        // Jalankan migrasi pada SQLite
        $this->newLine();
        $this->info('Menjalankan migrasi skema pada SQLite...');
        Artisan::call('migrate', [
            '--database' => 'sqlite_target',
            '--force' => true,
        ]);
        $this->info('✓ Skema berhasil dibuat di SQLite');

        // Migrasi data per tabel
        $this->newLine();
        $this->info('Memindahkan data...');
        $this->newLine();

        $target = DB::connection('sqlite_target');
        $target->statement('PRAGMA foreign_keys = OFF');

        $totalRows = 0;
        foreach ($this->tables as $table) {
            $count = $this->migrateTable($table, $target);
            $totalRows += $count;
        }

        $target->statement('PRAGMA foreign_keys = ON');

        $this->newLine();
        $this->info("✓ Selesai! Total $totalRows baris dipindahkan.");
        $this->newLine();
        $this->warn('Langkah selanjutnya — update .env:');
        $this->line('  1. Ubah DB_CONNECTION=sqlite');
        $this->line('  2. Ubah DB_DATABASE='.str_replace('\\', '/', $sqlitePath));
        $this->line('  3. Hapus atau comment baris DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_ENCRYPT');
        $this->line('  4. Jalankan: php artisan config:clear');
        $this->newLine();

        return self::SUCCESS;
    }

    private function migrateTable(string $table, \Illuminate\Database\Connection $target): int
    {
        if (! Schema::hasTable($table)) {
            $this->warn("  [skip] $table — tidak ditemukan di SQL Server");

            return 0;
        }

        $rows = DB::table($table)->get()->map(fn ($row) => $this->normalizeRow((array) $row))->toArray();
        $count = count($rows);

        if ($count === 0) {
            $this->line("  [kosong] $table");

            return 0;
        }

        // Hapus data lama di target lalu insert batch
        $target->table($table)->delete();

        foreach (array_chunk($rows, 100) as $chunk) {
            $target->table($table)->insert($chunk);
        }

        $this->info("  ✓ $table — $count baris");

        return $count;
    }

    private function normalizeRow(array $row): array
    {
        return array_map(function ($value) {
            // DateTime → string
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d H:i:s');
            }
            // Boolean → integer (SQLite tidak punya tipe boolean)
            if (is_bool($value)) {
                return $value ? 1 : 0;
            }
            // Resource (binary) → null
            if (is_resource($value)) {
                return null;
            }

            return $value;
        }, $row);
    }
}
