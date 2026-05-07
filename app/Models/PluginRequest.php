<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PluginRequest extends Model
{
    protected $fillable = [
        'name',
        'description',
        'requested_by',
        'status',
        'admin_note',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
