<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VanTransferItem extends Model
{
    protected $fillable = ['van_transfer_id', 'product_id', 'quantity'];
    protected $casts = ['quantity' => 'integer'];

    public function vanTransfer(): BelongsTo { return $this->belongsTo(VanTransfer::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
