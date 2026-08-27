<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $created_by
 * @property int|null $assigned_to
 * @property int|null $reviewer_id
 * @property int|null $final_approver_id
 * @property int|null $customer_id
 * @property string $title
 * @property string|null $note
 * @property Carbon|null $due_date
 * @property TaskStatus $status
 * @property string|null $priority
 * @property bool $requires_approval
 * @property array<string, mixed>|null $checklist
 * @property string|null $completion_notes
 * @property string|null $decision_reason
 * @property Carbon|null $completed_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $started_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $reopened_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Task extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'created_by', 'assigned_to', 'reviewer_id',
        'final_approver_id', 'customer_id', 'title', 'note', 'due_date',
        'status', 'priority', 'requires_approval', 'checklist',
        'completion_notes', 'decision_reason', 'completed_at', 'accepted_at',
        'started_at', 'submitted_at', 'approved_at', 'rejected_at',
        'reopened_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'requires_approval' => 'boolean',
            'checklist' => 'array',
            'completed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'reopened_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approver_id');
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return MorphMany<ApprovalRequest, $this> */
    public function approvals(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    /** @return MorphOne<ApprovalRequest, $this> */
    public function latestApproval(): MorphOne
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }

    /** @return MorphMany<Photo, $this> */
    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photable');
    }
}
