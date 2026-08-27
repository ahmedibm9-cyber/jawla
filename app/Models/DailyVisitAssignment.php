<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\DailyVisitAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int $customer_id
 * @property Carbon $visit_date
 * @property string $status
 * @property int $sort_order
 * @property int $assigned_by
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property int|null $approved_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DailyVisitAssignment extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<DailyVisitAssignmentFactory> */
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

    protected $with = ['latestApproval'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
}
