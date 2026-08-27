<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $alarm_id
 * @property int $user_id
 * @property bool $acknowledged
 * @property bool $resolved
 * @property Carbon|null $read_at
 * @property Carbon|null $acknowledged_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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

    /** @return BelongsTo<Alarm, $this> */
    public function alarm(): BelongsTo
    {
        return $this->belongsTo(Alarm::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
