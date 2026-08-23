<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class DailyVisitAssignment extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'customer_id', 'visit_date',
        'status', 'sort_order', 'assigned_by',
        'submitted_at', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function latestApproval(): MorphOne
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }
}
