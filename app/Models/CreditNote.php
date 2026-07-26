<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}
