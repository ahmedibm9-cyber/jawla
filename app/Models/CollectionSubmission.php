<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int $user_id
 * @property int $invoice_id
 * @property int $visit_id
 * @property int|null $payment_id
 * @property string $amount
 * @property string $method
 * @property string|null $reference_number
 * @property string|null $notes
 * @property string $status
 * @property Carbon|null $captured_at
 * @property int|null $supervisor_reviewed_by
 * @property Carbon|null $supervisor_reviewed_at
 * @property int|null $finance_reviewed_by
 * @property Carbon|null $finance_reviewed_at
 * @property int|null $reconciled_by
 * @property Carbon|null $reconciled_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CollectionSubmission extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'customer_id', 'user_id', 'invoice_id', 'visit_id', 'payment_id', 'amount', 'method', 'reference_number', 'notes', 'status', 'captured_at', 'supervisor_reviewed_by', 'supervisor_reviewed_at', 'finance_reviewed_by', 'finance_reviewed_at', 'reconciled_by', 'reconciled_at', 'reviewed_by', 'reviewed_at', 'review_reason'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'captured_at' => 'datetime',
            'supervisor_reviewed_at' => 'datetime',
            'finance_reviewed_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
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
