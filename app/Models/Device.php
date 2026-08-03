<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'device_uuid', 'name', 'platform',
        'fingerprint_hash', 'status', 'metadata', 'last_seen_at',
        'approved_by', 'approved_at', 'revoked_by', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeviceStatus::class,
            'metadata' => 'array',
            'last_seen_at' => 'datetime',
            'approved_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
