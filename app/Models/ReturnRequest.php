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
 * @property int $user_id
 * @property int|null $visit_id
 * @property int|null $against_invoice_id
 * @property int|null $return_record_id
 * @property int|null $destination_warehouse_id
 * @property int|null $quarantine_warehouse_id
 * @property string $request_number
 * @property string $status
 * @property string|null $reason
 * @property string $total
 * @property Carbon|null $submitted_at
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property int|null $received_by
 * @property Carbon|null $received_at
 * @property string|null $receipt_notes
 * @property string|null $decision_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class ReturnRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'customer_id', 'user_id', 'visit_id', 'against_invoice_id', 'return_record_id', 'destination_warehouse_id', 'quarantine_warehouse_id', 'request_number', 'status', 'reason', 'total', 'submitted_at', 'approved_by', 'approved_at', 'received_by', 'received_at', 'receipt_notes', 'decision_reason'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'received_at' => 'datetime'];
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
    public function againstInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'against_invoice_id');
    }

    /** @return BelongsTo<ReturnRecord, $this> */
    public function returnRecord(): BelongsTo
    {
        return $this->belongsTo(ReturnRecord::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function quarantineWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'quarantine_warehouse_id');
    }

    /** @return HasMany<ReturnRequestItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
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
