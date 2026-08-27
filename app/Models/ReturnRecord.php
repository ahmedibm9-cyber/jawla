<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ReturnRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int $user_id
 * @property int|null $visit_id
 * @property int|null $against_invoice_id
 * @property int|null $destination_warehouse_id
 * @property int|null $quarantine_warehouse_id
 * @property string $return_number
 * @property string $total
 * @property string|null $reason
 * @property string $status
 * @property Carbon|null $returned_at
 * @property Carbon|null $posting_date
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class ReturnRecord extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    /** @use HasFactory<ReturnRecordFactory> */
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'visit_id',
        'against_invoice_id', 'destination_warehouse_id', 'quarantine_warehouse_id', 'return_number', 'total',
        'reason', 'status', 'returned_at',
        'posting_date', 'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'returned_at' => 'datetime',
        'posting_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function againstInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'against_invoice_id');
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

    /** @return BelongsTo<User, $this> */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return HasMany<ReturnItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
