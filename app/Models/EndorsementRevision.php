<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class EndorsementRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'endorsement_id',
        'revision_date',
        'note',
        'uploaded_to_drive',
        'is_approved',
    ];

    protected $casts = [
        'revision_date' => 'date',
        'uploaded_to_drive' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function endorsement(): BelongsTo
    {
        return $this->belongsTo(Endorsement::class);
    }
}
