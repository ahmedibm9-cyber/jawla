<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsInTransit extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'goods_in_transit';

    protected $fillable = [
        'company_id', 'purchase_order_id', 'supplier_id', 'shipment_number',
        'status', 'estimated_arrival_date',
        'shipping_cost', 'customs_cost', 'clearance_cost', 'freight_cost',
        'posting_date', 'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'estimated_arrival_date' => 'date',
        'shipping_cost' => 'decimal:2',
        'customs_cost' => 'decimal:2',
        'clearance_cost' => 'decimal:2',
        'freight_cost' => 'decimal:2',
        'posting_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsInTransitItem::class);
    }

    public function landedCosts(): HasMany
    {
        return $this->hasMany(LandedCost::class);
    }
}
