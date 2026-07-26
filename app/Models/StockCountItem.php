<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Model;

class StockCountItem extends Model
{
    use AppendOnly;

    protected $fillable = [
        'stock_count_session_id', 'product_id', 'batch_id',
        'expected_quantity', 'physical_quantity', 'variance',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:3',
        'physical_quantity' => 'decimal:3',
        'variance' => 'decimal:3',
    ];
}
