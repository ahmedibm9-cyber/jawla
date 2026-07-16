<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function goodsInTransit(): BelongsTo
    {
        return $this->belongsTo(GoodsInTransit::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
