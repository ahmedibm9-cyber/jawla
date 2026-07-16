<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id', 'price_list_id', 'price', 'uom',
        'min_quantity', 'customer_id', 'valid_from', 'valid_upto', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_quantity' => 'decimal:3',
        'valid_from' => 'date',
        'valid_upto' => 'date',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
