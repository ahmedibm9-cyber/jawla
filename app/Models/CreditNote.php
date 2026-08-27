<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int $invoice_id
 * @property int|null $return_id
 * @property int $created_by
 * @property string $credit_number
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $total
 * @property string $status
 * @property string|null $reason
 * @property Carbon $issued_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CreditNote extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'customer_id', 'invoice_id', 'return_id', 'created_by',
        'credit_number', 'subtotal', 'tax_amount', 'total', 'status', 'reason', 'issued_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'issued_at' => 'immutable_datetime',
    ];

    /** @return HasMany<CreditNoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
