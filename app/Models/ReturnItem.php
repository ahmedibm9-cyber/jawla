<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $return_id
 * @property int $invoice_item_id
 * @property int $product_id
 * @property int|null $batch_id
 * @property string $condition
 * @property string $quantity
 * @property string $unit_price
 * @property string $line_total
 * @property string $tax_amount
 * @property string $total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ReturnItem extends Model
{
    use AppendOnly;

    protected $fillable = [
        'return_id', 'invoice_item_id', 'product_id', 'batch_id', 'condition',
        'quantity', 'unit_price', 'line_total', 'tax_amount', 'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /** @return BelongsTo<ReturnRecord, $this> */
    public function return(): BelongsTo
    {
        return $this->belongsTo(ReturnRecord::class, 'return_id');
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
