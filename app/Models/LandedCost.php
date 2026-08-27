<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $goods_in_transit_id
 * @property int|null $purchase_order_id
 * @property string $cost_type
 * @property string $amount
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LandedCost extends Model
{
    protected $fillable = [
        'goods_in_transit_id', 'purchase_order_id',
        'cost_type', 'amount', 'notes',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    /** @return BelongsTo<GoodsInTransit, $this> */
    public function goodsInTransit(): BelongsTo
    {
        return $this->belongsTo(GoodsInTransit::class);
    }
}
