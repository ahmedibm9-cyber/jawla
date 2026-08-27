<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int $user_id
 * @property int|null $invoice_id
 * @property int|null $visit_id
 * @property int|null $mode_of_payment_id
 * @property string $amount
 * @property string $allocated_amount
 * @property string $unallocated_amount
 * @property string|null $intent_id
 * @property string $payment_number
 * @property string $method
 * @property string $exchange_rate
 * @property string $base_amount
 * @property Carbon|null $collected_at
 * @property Carbon|null $posting_date
 * @property string|null $notes
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Payment extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'invoice_id', 'visit_id',
        'mode_of_payment_id', 'amount', 'allocated_amount', 'unallocated_amount', 'intent_id',
        'payment_number', 'method', 'exchange_rate', 'base_amount',
        'collected_at', 'posting_date', 'notes',
        'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'base_amount' => 'decimal:2',
        'collected_at' => 'datetime',
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

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return BelongsTo<ModeOfPayment, $this> */
    public function modeOfPayment(): BelongsTo
    {
        return $this->belongsTo(ModeOfPayment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
