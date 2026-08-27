<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SupplierQuotationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_request_id
 * @property int $company_id
 * @property int $supplier_id
 * @property int $product_id
 * @property string $quantity
 * @property string $unit_price
 * @property string $currency
 * @property string|null $payment_terms
 * @property int|null $delivery_time_days
 * @property Carbon|null $valid_until
 * @property string $status
 * @property int|null $reviewed_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SupplierQuotation extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<SupplierQuotationFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_request_id', 'company_id', 'supplier_id', 'product_id',
        'quantity', 'unit_price', 'currency', 'payment_terms',
        'delivery_time_days', 'valid_until', 'status', 'reviewed_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'valid_until' => 'date',
    ];

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
