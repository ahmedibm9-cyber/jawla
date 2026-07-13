<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlarmRead extends Model
{
    protected $fillable = [
        'alarm_id', 'user_id', 'acknowledged', 'resolved',
        'read_at', 'acknowledged_at', 'resolved_at',
    ];

    protected $casts = [
        'acknowledged' => 'boolean',
        'resolved' => 'boolean',
        'read_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function alarm(): BelongsTo
    {
        return $this->belongsTo(Alarm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}