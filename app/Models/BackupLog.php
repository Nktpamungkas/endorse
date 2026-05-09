<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    protected $fillable = [
        'triggered_by',
        'trigger_type',
        'status',
        'database_driver',
        'timezone',
        'scheduled_for',
        'started_at',
        'finished_at',
        'file_name',
        'file_path',
        'file_size_bytes',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'file_size_bytes' => 'integer',
        ];
    }

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
