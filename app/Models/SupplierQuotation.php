<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierQuotation extends Model
{
    use HasFactory;

    use BelongsToCompany;

    protected $fillable = [
        'purchase_request_id', 'company_id', 'supplier_id', 'product_id',
        'quantity', 'unit_price', 'currency', 'payment_terms',
        'delivery_time_days', 'valid_until', 'status', 'reviewed_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'valid_until' => 'date',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}