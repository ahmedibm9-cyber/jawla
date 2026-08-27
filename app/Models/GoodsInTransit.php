<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\GoodsInTransitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $purchase_order_id
 * @property int $supplier_id
 * @property string|null $shipment_number
 * @property string $status
 * @property Carbon|null $estimated_arrival_date
 * @property string $shipping_cost
 * @property string $customs_cost
 * @property string $clearance_cost
 * @property string $freight_cost
 * @property Carbon|null $posting_date
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GoodsInTransit extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<GoodsInTransitFactory> */
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

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<GoodsInTransitItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsInTransitItem::class);
    }

    /** @return HasMany<LandedCost, $this> */
    public function landedCosts(): HasMany
    {
        return $this->hasMany(LandedCost::class);
    }
}
