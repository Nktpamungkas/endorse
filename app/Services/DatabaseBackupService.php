<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\BackupSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DatabaseBackupService
{
    public const BACKUP_DIRECTORY = 'app/backups/database';

    public function run(
        string $triggerType = 'manual',
        ?User $triggeredBy = null,
        ?CarbonImmutable $scheduledFor = null,
        ?BackupSetting $setting = null
    ): BackupLog {
        $setting ??= BackupSetting::current();
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $filesystem = new Filesystem();
        $directory = storage_path(self::BACKUP_DIRECTORY);

        $filesystem->ensureDirectoryExists($directory);

        $log = BackupLog::create([
            'triggered_by' => $triggeredBy?->id,
            'trigger_type' => $triggerType,
            'status' => 'running',
            'database_driver' => $driver,
            'timezone' => $setting->timezone ?: 'Asia/Jakarta',
            'scheduled_for' => $scheduledFor?->setTimezone(config('app.timezone')),
            'started_at' => now(),
            'message' => 'Backup dimulai.',
        ]);

        try {
            $content = match ($driver) {
                'sqlsrv' => $this->buildSqlServerBackup($connection),
                'sqlite' => $this->buildSqliteBackup($connection),
                default => throw new \RuntimeException("Driver database [{$driver}] belum didukung untuk backup."),
            };

            $filename = sprintf(
                '%s_%s.sql',
                Str::slug((string) config('app.name', 'database'), '_'),
                now()->format('Ymd_His')
            );

            $path = $directory.DIRECTORY_SEPARATOR.$filename;
            $filesystem->put($path, $content);
            $removed = $this->pruneOldBackups($filesystem, $directory, (int) $setting->keep_days, $path);

            $log->forceFill([
                'status' => 'success',
                'finished_at' => now(),
                'file_name' => $filename,
                'file_path' => $path,
                'file_size_bytes' => $filesystem->size($path),
                'message' => $removed > 0
                    ? "Backup selesai. {$removed} file backup lama dibersihkan."
                    : 'Backup selesai.',
            ])->save();
        } catch (Throwable $exception) {
            $log->forceFill([
                'status' => 'failed',
                'finished_at' => now(),
                'message' => mb_substr($exception->getMessage(), 0, 65535),
            ])->save();

            report($exception);

            throw $exception;
        }

        return $log->fresh();
    }

    protected function buildSqlServerBackup(ConnectionInterface $connection): string
    {
        $tables = $connection->select(
            <<<'SQL'
                SELECT
                    s.name AS TABLE_SCHEMA,
                    t.name AS TABLE_NAME
                FROM sys.tables t
                INNER JOIN sys.schemas s ON s.schema_id = t.schema_id
                WHERE t.is_ms_shipped = 0
                ORDER BY s.name, t.name
            SQL
        );

        $lines = $this->backupHeader($connection, 'sqlsrv');
        $lines[] = 'SET NOCOUNT ON;';

        foreach ($tables as $table) {
            $schema = $table->TABLE_SCHEMA;
            $name = $table->TABLE_NAME;
            $qualifiedTable = $this->wrapSqlServerQualifiedName($schema, $name);
            $objectName = $schema.'.'.$name;
            $columns = $this->fetchSqlServerColumns($connection, $objectName);

            $columnNames = array_map(
                fn ($column) => $this->wrapSqlServerIdentifier($column->COLUMN_NAME),
                $columns
            );

            $hasIdentity = collect($columns)->contains(fn ($column) => (bool) $column->IS_IDENTITY);

            $lines[] = '';
            $lines[] = '-- ------------------------------------------------------------------';
            $lines[] = '-- Table: '.$qualifiedTable;
            $lines[] = "IF OBJECT_ID(N'{$objectName}', N'U') IS NOT NULL DROP TABLE {$qualifiedTable};";
            $lines[] = $this->buildSqlServerCreateTableStatement($connection, $schema, $name, $columns);

            if ($hasIdentity) {
                $lines[] = 'SET IDENTITY_INSERT '.$qualifiedTable.' ON;';
            }

            $batch = [];
            foreach ($connection->table($schema.'.'.$name)->orderByRaw('1')->cursor() as $row) {
                $values = [];

                foreach ($columns as $column) {
                    $values[] = $this->formatSqlValue(
                        $row->{$column->COLUMN_NAME} ?? null,
                        (string) $column->DATA_TYPE,
                        'sqlsrv'
                    );
                }

                $batch[] = '('.implode(', ', $values).')';

                if (count($batch) >= 200) {
                    $lines[] = 'INSERT INTO '.$qualifiedTable.' ('.implode(', ', $columnNames).') VALUES';
                    $lines[] = implode(",\n", $batch).';';
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $lines[] = 'INSERT INTO '.$qualifiedTable.' ('.implode(', ', $columnNames).') VALUES';
                $lines[] = implode(",\n", $batch).';';
            }

            if ($hasIdentity) {
                $lines[] = 'SET IDENTITY_INSERT '.$qualifiedTable.' OFF;';
            }
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    protected function fetchSqlServerColumns(ConnectionInterface $connection, string $objectName): array
    {
        return $connection->select(
            <<<'SQL'
                SELECT
                    c.name AS COLUMN_NAME,
                    ty.name AS DATA_TYPE,
                    c.max_length AS CHARACTER_MAXIMUM_LENGTH,
                    c.precision AS NUMERIC_PRECISION,
                    c.scale AS NUMERIC_SCALE,
                    c.is_nullable AS IS_NULLABLE,
                    c.is_identity AS IS_IDENTITY,
                    ic.seed_value AS IDENTITY_SEED,
                    ic.increment_value AS IDENTITY_INCREMENT,
                    dc.definition AS COLUMN_DEFAULT
                FROM sys.columns c
                INNER JOIN sys.types ty ON ty.user_type_id = c.user_type_id
                LEFT JOIN sys.default_constraints dc ON dc.parent_object_id = c.object_id
                    AND dc.parent_column_id = c.column_id
                LEFT JOIN sys.identity_columns ic ON ic.object_id = c.object_id
                    AND ic.column_id = c.column_id
                WHERE c.object_id = OBJECT_ID(?)
                ORDER BY c.column_id
            SQL,
            [$objectName]
        );
    }

    protected function buildSqlServerCreateTableStatement(
        ConnectionInterface $connection,
        string $schema,
        string $name,
        array $columns
    ): string {
        $qualifiedTable = $this->wrapSqlServerQualifiedName($schema, $name);
        $definitions = [];

        foreach ($columns as $column) {
            $line = '    '.$this->wrapSqlServerIdentifier($column->COLUMN_NAME).' '.$this->formatSqlServerColumnType($column);

            if ((bool) $column->IS_IDENTITY) {
                $seed = (int) ($column->IDENTITY_SEED ?? 1);
                $increment = (int) ($column->IDENTITY_INCREMENT ?? 1);
                $line .= " IDENTITY({$seed},{$increment})";
            }

            if ($column->COLUMN_DEFAULT) {
                $line .= ' DEFAULT '.$column->COLUMN_DEFAULT;
            }

            $line .= (bool) $column->IS_NULLABLE ? ' NULL' : ' NOT NULL';
            $definitions[] = $line;
        }

        $primaryKey = $connection->selectOne(
            <<<'SQL'
                SELECT kc.name AS CONSTRAINT_NAME
                FROM sys.key_constraints kc
                WHERE kc.parent_object_id = OBJECT_ID(?)
                  AND kc.type = 'PK'
            SQL,
            [$schema.'.'.$name]
        );

        if ($primaryKey) {
            $primaryKeyColumns = $connection->select(
                <<<'SQL'
                    SELECT c.name AS COLUMN_NAME
                    FROM sys.key_constraints kc
                    INNER JOIN sys.index_columns ic
                        ON ic.object_id = kc.parent_object_id
                        AND ic.index_id = kc.unique_index_id
                    INNER JOIN sys.columns c
                        ON c.object_id = ic.object_id
                        AND c.column_id = ic.column_id
                    WHERE kc.parent_object_id = OBJECT_ID(?)
                      AND kc.type = 'PK'
                    ORDER BY ic.key_ordinal
                SQL,
                [$schema.'.'.$name]
            );

            $definitions[] = '    CONSTRAINT '.$this->wrapSqlServerIdentifier($primaryKey->CONSTRAINT_NAME)
                .' PRIMARY KEY ('
                .collect($primaryKeyColumns)->map(fn ($column) => $this->wrapSqlServerIdentifier($column->COLUMN_NAME))->implode(', ')
                .')';
        }

        return "CREATE TABLE {$qualifiedTable} (\n".implode(",\n", $definitions)."\n);";
    }

    protected function formatSqlServerColumnType(object $column): string
    {
        $type = strtolower((string) $column->DATA_TYPE);
        $length = (int) $column->CHARACTER_MAXIMUM_LENGTH;
        $precision = (int) $column->NUMERIC_PRECISION;
        $scale = (int) $column->NUMERIC_SCALE;

        return match ($type) {
            'nvarchar', 'nchar' => $type.'('.($length === -1 ? 'max' : (int) ($length / 2)).')',
            'varchar', 'char', 'varbinary', 'binary' => $type.'('.($length === -1 ? 'max' : $length).')',
            'decimal', 'numeric' => $type."({$precision},{$scale})",
            'datetime2', 'datetimeoffset', 'time' => $type."({$scale})",
            default => $type,
        };
    }

    protected function buildSqliteBackup(ConnectionInterface $connection): string
    {
        $tables = $connection->select(
            <<<'SQL'
                SELECT name, sql
                FROM sqlite_master
                WHERE type = 'table' AND name NOT LIKE 'sqlite_%'
                ORDER BY name
            SQL
        );

        $lines = $this->backupHeader($connection, 'sqlite');

        foreach ($tables as $table) {
            $tableName = $table->name;
            $qualifiedTable = $this->wrapSqliteIdentifier($tableName);

            $columns = $connection->select('PRAGMA table_info('.$qualifiedTable.')');
            $columnNames = array_map(
                fn ($column) => $this->wrapSqliteIdentifier($column->name),
                $columns
            );

            $lines[] = '';
            $lines[] = '-- ------------------------------------------------------------------';
            $lines[] = '-- Table: '.$qualifiedTable;
            $lines[] = 'DROP TABLE IF EXISTS '.$qualifiedTable.';';
            $lines[] = (string) $table->sql.';';

            $batch = [];
            foreach ($connection->table($tableName)->cursor() as $row) {
                $values = [];

                foreach ($columns as $column) {
                    $values[] = $this->formatSqlValue(
                        $row->{$column->name} ?? null,
                        (string) $column->type,
                        'sqlite'
                    );
                }

                $batch[] = '('.implode(', ', $values).')';

                if (count($batch) >= 200) {
                    $lines[] = 'INSERT INTO '.$qualifiedTable.' ('.implode(', ', $columnNames).') VALUES';
                    $lines[] = implode(",\n", $batch).';';
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $lines[] = 'INSERT INTO '.$qualifiedTable.' ('.implode(', ', $columnNames).') VALUES';
                $lines[] = implode(",\n", $batch).';';
            }
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    protected function backupHeader(ConnectionInterface $connection, string $driver): array
    {
        return [
            '-- Database backup generated by '.static::class,
            '-- Generated at: '.now()->toDateTimeString(),
            '-- Connection driver: '.$driver,
            '-- Database name: '.(string) $connection->getDatabaseName(),
        ];
    }

    protected function formatSqlValue(mixed $value, string $dataType, string $driver): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof DateTimeInterface) {
            return $this->quoteString($value->format('Y-m-d H:i:s.u'), $driver);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $normalizedType = strtolower($dataType);

        if (in_array($normalizedType, ['binary', 'varbinary', 'image', 'timestamp', 'rowversion'], true)) {
            $bytes = is_resource($value) ? stream_get_contents($value) : (string) $value;

            return '0x'.strtoupper(bin2hex($bytes));
        }

        if (is_numeric($value) && ! in_array($normalizedType, ['char', 'nchar', 'varchar', 'nvarchar', 'text', 'ntext'], true)) {
            return (string) $value;
        }

        return $this->quoteString((string) $value, $driver);
    }

    protected function quoteString(string $value, string $driver): string
    {
        $escaped = str_replace("'", "''", $value);

        return $driver === 'sqlsrv'
            ? "N'{$escaped}'"
            : "'{$escaped}'";
    }

    protected function wrapSqlServerIdentifier(string $name): string
    {
        return '['.str_replace(']', ']]', $name).']';
    }

    protected function wrapSqlServerQualifiedName(string $schema, string $table): string
    {
        return $this->wrapSqlServerIdentifier($schema).'.'.$this->wrapSqlServerIdentifier($table);
    }

    protected function wrapSqliteIdentifier(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }

    protected function pruneOldBackups(Filesystem $filesystem, string $directory, int $keepDays, string $currentFile): int
    {
        if ($keepDays <= 0) {
            return 0;
        }

        $threshold = now()->subDays($keepDays)->getTimestamp();
        $removed = 0;

        foreach ($filesystem->files($directory) as $file) {
            if ($file->getPathname() === $currentFile || $file->getExtension() !== 'sql') {
                continue;
            }

            if ($file->getMTime() < $threshold) {
                $filesystem->delete($file->getPathname());
                $removed++;
            }
        }

        return $removed;
    }
}
