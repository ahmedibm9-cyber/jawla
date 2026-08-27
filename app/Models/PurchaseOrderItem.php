<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property string $quantity
 * @property string $unit_price
 * @property string $line_total
 * @property string|null $received_quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'product_id',
        'quantity', 'unit_price', 'line_total', 'received_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'received_quantity' => 'decimal:3',
    ];

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
