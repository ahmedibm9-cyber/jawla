<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceQuotation extends Model
{
    protected $fillable = [
        'price_quotation_request_id', 'base_price',
        'manager_plus', 'manager_minus',
        'rep_plus', 'rep_minus',
        'priced_by', 'priced_at', 'valid_until',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'manager_plus' => 'decimal:2',
        'manager_minus' => 'decimal:2',
        'rep_plus' => 'decimal:2',
        'rep_minus' => 'decimal:2',
        'priced_at' => 'datetime',
        'valid_until' => 'date',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PriceQuotationRequest::class, 'price_quotation_request_id');
    }

    public function pricedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'priced_by');
    }
}
