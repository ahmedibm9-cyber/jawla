<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\OutOfStockRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int $customer_id
 * @property int $product_id
 * @property string $quantity_requested
 * @property string|null $notes
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OutOfStockRequest extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<OutOfStockRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'customer_id', 'product_id',
        'quantity_requested', 'notes', 'status',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3',
    ];

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
}
