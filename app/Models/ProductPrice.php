<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property int $price_list_id
 * @property string $price
 * @property string|null $uom
 * @property string|null $min_quantity
 * @property int|null $customer_id
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_upto
 * @property bool $is_active
 * @property int|null $created_by
 * @property string|null $reason
 * @property bool $is_customer_override
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProductPrice extends Model
{
    protected $fillable = [
        'product_id', 'price_list_id', 'price', 'uom',
        'min_quantity', 'customer_id', 'valid_from', 'valid_upto', 'is_active',
        'created_by', 'reason', 'is_customer_override',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_quantity' => 'decimal:3',
        'valid_from' => 'date',
        'valid_upto' => 'date',
        'is_active' => 'boolean',
        'is_customer_override' => 'boolean',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<PriceList, $this> */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
