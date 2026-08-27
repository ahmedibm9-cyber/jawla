<?php

namespace App\Models;

use App\Enums\ApprovalStepStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $approval_request_id
 * @property int $sequence
 * @property int $approver_id
 * @property ApprovalStepStatus $status
 * @property string|null $decision_reason
 * @property Carbon|null $decided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApprovalStep extends Model
{
    protected $fillable = [
        'approval_request_id', 'sequence', 'approver_id', 'status',
        'decision_reason', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStepStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ApprovalRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
