<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use AppendOnly;
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'invoice_id', 'visit_id',
        'mode_of_payment_id', 'amount', 'allocated_amount', 'unallocated_amount', 'intent_id',
        'method', 'exchange_rate', 'base_amount',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function modeOfPayment(): BelongsTo
    {
        return $this->belongsTo(ModeOfPayment::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
