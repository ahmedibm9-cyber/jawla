<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CashReconciliationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int $work_session_id
 * @property string $expected_amount
 * @property string $counted_amount
 * @property string $variance
 * @property string|null $notes
 * @property string $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CashReconciliation extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    /** @use HasFactory<CashReconciliationFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'work_session_id',
        'expected_amount', 'counted_amount', 'variance', 'notes',
        'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'counted_amount' => 'decimal:2',
        'variance' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<WorkSession, $this> */
    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }

    public function isBalanced(): bool
    {
        return (float) $this->variance === 0.0;
    }
}
