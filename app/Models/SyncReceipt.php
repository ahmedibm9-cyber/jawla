<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Server-side idempotency ledger for offline sync (CG2). One row per successfully
 * applied operation, keyed by (company_id, idempotency_key); a replay of the same
 * key returns the stored response instead of re-applying.
 */
class SyncReceipt extends Model
{
    use AppendOnly;
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'idempotency_key', 'operation_type', 'response',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
