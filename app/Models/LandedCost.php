<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandedCost extends Model
{
    protected $fillable = [
        'goods_in_transit_id', 'purchase_order_id',
        'cost_type', 'amount', 'notes',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function goodsInTransit(): BelongsTo { return $this->belongsTo(GoodsInTransit::class); }
}