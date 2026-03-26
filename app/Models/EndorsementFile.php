<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndorsementFile extends Model
{
    use HasFactory;

    public const CATEGORY_LABELS = [
        'image' => 'Gambar',
        'video' => 'Video',
        'audio' => 'Audio',
        'pdf' => 'PDF',
        'document' => 'Dokumen',
        'archive' => 'Arsip',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'endorsement_id',
        'uploaded_by',
        'disk',
        'directory',
        'stored_name',
        'original_name',
        'extension',
        'mime_type',
        'category',
        'size_bytes',
        'sha256_checksum',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function endorsement(): BelongsTo
    {
        return $this->belongsTo(Endorsement::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
