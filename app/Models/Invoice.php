<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use AppendOnly;
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'visit_id',
        'proforma_invoice_id', 'invoice_number', 'status',
        'subtotal', 'vat_amount', 'total', 'paid_amount', 'credited_amount', 'remaining_amount',
        'eta_qr', 'zatca_qr', 'posting_date', 'issued_at',
        'cancelled_at', 'cancelled_by', 'amended_from',
        'uuid', 'hash_chain', 'cryptographic_stamp', 'zatca_status', 'zatca_submitted_at', 'zatca_response',
        'eta_status', 'eta_submission_uuid', 'eta_long_id', 'eta_submitted_at', 'eta_response',
        'snapshot_company', 'snapshot_customer', 'snapshot_items', 'snapshot_totals',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'credited_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'posting_date' => 'date',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'zatca_submitted_at' => 'datetime',
        'zatca_status' => 'string',
        'eta_submitted_at' => 'datetime',
        'eta_response' => 'array',
        'snapshot_company' => 'array',
        'snapshot_customer' => 'array',
        'snapshot_items' => 'array',
        'snapshot_totals' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

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

    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function amendedFrom(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'amended_from');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRecord::class, 'against_invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceTax::class);
    }
}
