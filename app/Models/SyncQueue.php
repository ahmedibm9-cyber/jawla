<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncQueue extends Model
{
    protected $table = 'sync_queue';

    protected $fillable = [
        'user_id', 'transaction_type', 'entity_id', 'data_json',
        'status', 'idempotency_key', 'error_message', 'retry_count', 'synced_at',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
