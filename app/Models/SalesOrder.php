<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int|null $customer_outlet_id
 * @property int $user_id
 * @property int|null $invoice_id
 * @property string $order_number
 * @property string $status
 * @property Carbon|null $requested_delivery_date
 * @property string|null $notes
 * @property string $subtotal
 * @property string $total
 * @property Carbon|null $submitted_at
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property Carbon|null $fulfilled_at
 * @property int|null $cancelled_by
 * @property Carbon|null $cancelled_at
 * @property string|null $decision_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SalesOrder extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'customer_id', 'customer_outlet_id', 'user_id', 'invoice_id', 'order_number', 'status', 'requested_delivery_date', 'notes', 'subtotal', 'total', 'submitted_at', 'approved_by', 'approved_at', 'fulfilled_at', 'cancelled_by', 'cancelled_at', 'decision_reason'];

    protected function casts(): array
    {
        return ['requested_delivery_date' => 'date', 'subtotal' => 'decimal:2', 'total' => 'decimal:2', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'fulfilled_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<CustomerOutlet, $this> */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(CustomerOutlet::class, 'customer_outlet_id');
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

    /** @return HasMany<SalesOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
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
