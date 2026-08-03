<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Server-side idempotency ledger for offline sync (CG2). One row per successfully
 * applied operation, keyed by (company_id, user_id, idempotency_key); a replay
 * by the same user returns the stored response instead of re-applying.
 */
class SyncReceipt extends Model
{
    use AppendOnly;
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'idempotency_key', 'operation_type', 'protocol_version', 'response', 'payload_hash', 'device_id',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::updating(function (SyncReceipt $receipt): void {
            if ($receipt->isDirty('payload_hash') && $receipt->getOriginal('payload_hash') !== null) {
                $receipt->payload_hash = $receipt->getOriginal('payload_hash');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
