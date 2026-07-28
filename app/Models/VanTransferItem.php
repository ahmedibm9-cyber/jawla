<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function vanTransfer(): BelongsTo
    {
        return $this->belongsTo(VanTransfer::class);
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
