<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ProformaInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int $user_id
 * @property int|null $visit_id
 * @property int|null $price_quotation_id
 * @property string $proforma_number
 * @property string $subtotal
 * @property string $vat_amount
 * @property string $total
 * @property int|null $company_bank_account_id
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $valid_until
 * @property Carbon|null $posting_date
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string $uuid
 * @property string|null $hash_chain
 * @property string|null $cryptographic_stamp
 * @property string $zatca_status
 * @property Carbon|null $zatca_submitted_at
 * @property string|null $zatca_response
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProformaInvoice extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<ProformaInvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'visit_id',
        'price_quotation_id', 'proforma_number',
        'subtotal', 'vat_amount', 'total',
        'company_bank_account_id', 'status', 'notes',
        'valid_until', 'posting_date',
        'cancelled_at', 'cancelled_by',
        'uuid', 'hash_chain', 'cryptographic_stamp', 'zatca_status', 'zatca_submitted_at', 'zatca_response',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'valid_until' => 'date',
        'posting_date' => 'date',
        'cancelled_at' => 'datetime',
        'zatca_submitted_at' => 'datetime',
        'zatca_status' => 'string',
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

    /** @return HasMany<ProformaInvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ProformaInvoiceItem::class);
    }

    /** @return BelongsTo<CompanyBankAccount, $this> */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id');
    }
}
