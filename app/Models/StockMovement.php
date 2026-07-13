<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'warehouse_id', 'product_id', 'batch_id',
        'quantity_change', 'valuation_rate', 'reason',
        'reference_type', 'reference_id', 'user_id', 'posting_date',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:3',
        'valuation_rate' => 'decimal:2',
        'posting_date' => 'date',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function batch(): BelongsTo { return $this->belongsTo(Batch::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}