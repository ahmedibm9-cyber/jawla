<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $supplier_id
 * @property string $order_number
 * @property string $status
 * @property Carbon $order_date
 * @property Carbon|null $expected_delivery_date
 * @property string|null $payment_terms
 * @property string $currency
 * @property string $subtotal
 * @property string $shipping_cost
 * @property string $total
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PurchaseOrder extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'supplier_id', 'order_number', 'status',
        'order_date', 'expected_delivery_date', 'payment_terms',
        'currency', 'subtotal', 'shipping_cost', 'total', 'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<PurchaseOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
