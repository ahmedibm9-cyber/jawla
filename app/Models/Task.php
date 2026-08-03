<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approver_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function latestApproval(): MorphOne
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photable');
    }
}
