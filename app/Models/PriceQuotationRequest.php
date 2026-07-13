<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceQuotationRequest extends Model
{
    use HasFactory;

    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'visit_id', 'product_id',
        'quantity_requested', 'status', 'requested_at',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'requested_at' => 'datetime',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function visit(): BelongsTo { return $this->belongsTo(Visit::class); }
    public function quotation(): BelongsTo { return $this->belongsTo(PriceQuotation::class, 'price_quotation_request_id'); }
}