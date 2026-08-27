<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\PurchaseRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int $supplier_id
 * @property int $product_id
 * @property string $quantity
 * @property string|null $offered_price
 * @property string $currency
 * @property string|null $payment_terms
 * @property string $status
 * @property Carbon|null $expires_at
 * @property int|null $purchase_order_id
 * @property int|null $sales_reviewed_by
 * @property Carbon|null $sales_reviewed_at
 * @property string|null $sales_review_notes
 * @property int|null $purchasing_reviewed_by
 * @property Carbon|null $purchasing_reviewed_at
 * @property string|null $purchasing_review_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PurchaseRequest extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<PurchaseRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'supplier_id', 'product_id',
        'quantity', 'offered_price', 'currency', 'payment_terms',
        'status', 'expires_at', 'purchase_order_id',
        'sales_reviewed_by', 'sales_reviewed_at', 'sales_review_notes',
        'purchasing_reviewed_by', 'purchasing_reviewed_at', 'purchasing_review_notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'offered_price' => 'decimal:2',
        'expires_at' => 'date',
        'sales_reviewed_at' => 'datetime',
        'purchasing_reviewed_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->endOfDay()->isPast();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function salesReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_reviewed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function purchasingReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchasing_reviewed_by');
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
