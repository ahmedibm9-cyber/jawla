<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property int $customer_id
 * @property int $user_id
 * @property int|null $visit_id
 * @property int|null $proforma_invoice_id
 * @property string $invoice_number
 * @property InvoiceStatus $status
 * @property string $subtotal
 * @property string $vat_amount
 * @property string $total
 * @property string $paid_amount
 * @property string $credited_amount
 * @property string $remaining_amount
 * @property string|null $eta_qr
 * @property string|null $zatca_qr
 * @property Carbon|null $posting_date
 * @property Carbon $issued_at
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property int|null $amended_from
 * @property string|null $hash_chain
 * @property string|null $cryptographic_stamp
 * @property string $zatca_status
 * @property Carbon|null $zatca_submitted_at
 * @property string|null $zatca_response
 * @property string $eta_status
 * @property string|null $eta_submission_uuid
 * @property string|null $eta_long_id
 * @property Carbon|null $eta_submitted_at
 * @property array<string, mixed>|null $eta_response
 * @property array<string, mixed>|null $snapshot_company
 * @property array<string, mixed>|null $snapshot_customer
 * @property list<array<string, mixed>>|null $snapshot_items
 * @property array<string, mixed>|null $snapshot_totals
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class Invoice extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    /** @use HasFactory<InvoiceFactory> */
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

    /** @return BelongsTo<ProformaInvoice, $this> */
    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id');
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function amendedFrom(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'amended_from');
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<ReturnRecord, $this> */
    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRecord::class, 'against_invoice_id');
    }

    /** @return HasMany<CreditNote, $this> */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    /** @return HasMany<InvoiceTax, $this> */
    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceTax::class);
    }
}
