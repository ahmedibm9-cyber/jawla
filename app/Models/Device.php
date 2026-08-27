<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property string $device_uuid
 * @property string $name
 * @property string $platform
 * @property string|null $fingerprint_hash
 * @property DeviceStatus $status
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $last_seen_at
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property int|null $revoked_by
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
