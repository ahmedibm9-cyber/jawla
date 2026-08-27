<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SyncReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Server-side idempotency ledger for offline sync (CG2). One row per successfully
 * applied operation, keyed by (company_id, user_id, idempotency_key); a replay
 * by the same user returns the stored response instead of re-applying.
 *
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property string $idempotency_key
 * @property string $operation_type
 * @property string $protocol_version
 * @property array<string, mixed>|null $response
 * @property string|null $payload_hash
 * @property string|null $device_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SyncReceipt extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    /** @use HasFactory<SyncReceiptFactory> */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
