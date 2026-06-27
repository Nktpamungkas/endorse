<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanColumn extends Model
{
    protected $fillable = ['user_id', 'slug', 'name', 'accent', 'position'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
