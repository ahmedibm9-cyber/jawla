<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $price_quotation_request_id
 * @property string $base_price
 * @property string $manager_plus
 * @property string $manager_minus
 * @property string $rep_plus
 * @property string $rep_minus
 * @property int $priced_by
 * @property Carbon|null $priced_at
 * @property Carbon|null $valid_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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

    /** @return BelongsTo<PriceQuotationRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(PriceQuotationRequest::class, 'price_quotation_request_id');
    }

    /** @return BelongsTo<User, $this> */
    public function pricedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'priced_by');
    }
}
