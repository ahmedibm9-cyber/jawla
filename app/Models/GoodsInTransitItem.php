<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $goods_in_transit_id
 * @property int $product_id
 * @property int|null $batch_id
 * @property string $quantity
 * @property string|null $received_quantity
 * @property string $unit_price
 * @property string $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GoodsInTransitItem extends Model
{
    protected $fillable = [
        'goods_in_transit_id', 'product_id', 'batch_id',
        'quantity', 'received_quantity', 'unit_price', 'currency',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
    ];

    /** @return BelongsTo<GoodsInTransit, $this> */
    public function goodsInTransit(): BelongsTo
    {
        return $this->belongsTo(GoodsInTransit::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Batch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
