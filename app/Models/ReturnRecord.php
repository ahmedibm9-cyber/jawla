<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRecord extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'visit_id',
        'against_invoice_id', 'return_number', 'total',
        'reason', 'status', 'returned_at',
        'posting_date', 'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'returned_at' => 'datetime',
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

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function againstInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'against_invoice_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
