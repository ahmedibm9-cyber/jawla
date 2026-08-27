<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\PriceQuotationRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property int $user_id
 * @property int|null $visit_id
 * @property int $product_id
 * @property string $quantity_requested
 * @property string $status
 * @property Carbon|null $requested_at
 * @property string|null $negotiated_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PriceQuotationRequest extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<PriceQuotationRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'customer_id', 'user_id', 'visit_id', 'product_id',
        'quantity_requested', 'status', 'requested_at', 'negotiated_price',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
        'requested_at' => 'datetime',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return HasOne<PriceQuotation, $this> */
    public function quotation(): HasOne
    {
        return $this->hasOne(PriceQuotation::class);
    }
}
