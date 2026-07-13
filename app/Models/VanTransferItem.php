<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VanTransferItem extends Model
{
    protected $fillable = ['van_transfer_id', 'product_id', 'batch_id', 'quantity'];
    protected $casts = ['quantity' => 'decimal:3'];

    public function vanTransfer(): BelongsTo { return $this->belongsTo(VanTransfer::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function batch(): BelongsTo { return $this->belongsTo(Batch::class); }
}