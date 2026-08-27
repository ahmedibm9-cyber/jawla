<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $van_transfer_id
 * @property int $product_id
 * @property int|null $batch_id
 * @property string $quantity
 * @property string|null $received_quantity
 * @property string|null $exception_quantity
 * @property string|null $exception_reason
 * @property Carbon|null $exceptioned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VanTransferItem extends Model
{
    use AppendOnly;

    protected $fillable = [
        'van_transfer_id', 'product_id', 'batch_id', 'quantity',
        'received_quantity', 'exception_quantity', 'exception_reason', 'exceptioned_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'exception_quantity' => 'decimal:3',
        'exceptioned_at' => 'datetime',
    ];

    /** @return BelongsTo<VanTransfer, $this> */
    public function vanTransfer(): BelongsTo
    {
        return $this->belongsTo(VanTransfer::class);
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
