<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class FileShare extends Model
{
    protected $fillable = [
        'file_id',
        'owner_id',
        'target_user_id',
        'permission',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
